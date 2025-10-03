<?php namespace App\Services\Sources;

use App\Exceptions\Http\{HttpAbstract, RepeatException};
use App\Helpers\Arr;
use App\Models\Dev\MarketplaceApiKey;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class Tokens extends SourceAbstract
{
    /** @var MarketplaceApiKey[] */
    private array $tokens = [];

    private int $throws = 0;

    public function __construct(string $market, string $operation, ?array $tokens = [], public bool $is_manual = false)
    {
        $query = MarketplaceApiKey::query()->where('active', 'Y')->where('marketplace', $market)->orderBy('last_request');

        if($this->is_manual) $query->whereIn('id', $tokens); else match($operation)
        {
            'PRODUCTS', 'PRICES' => $query->whereJsonContains('days', date('N') * 1), default => null
        };

        foreach($query->get() as $token) $this->tokens[$token->id] = $token; $this->skip = fn(array $attributes) => $attributes['token']->id;

        foreach(['success', 'abort'] as $state) $this->{$state} = fn(HttpAbstract $e) => $e->token->inset($state);

        foreach(['current', 'next'] as $operation) $this->handlers[$operation] = fn() => $this->{$operation}();

        $this->repeat = function(HttpAbstract $e)
        {
            $this->enqueue($e->endpoint, $this->throws++ === 0 ? 'next' : 'current', $e->method, $e->post, ...$e->custom);

            $this->skips[$e->token->id] = RepeatException::class; $e->token->inset('abort');
        };

        $this->throw = function(Throwable $e, $attributes, ...$data)
        {
            Log::channel('error')->error('Tokens Error | '.$e); $this->enqueue(...array_values($attributes), ...$data); $attributes['token']->inset('abort');
        };
    }

    public function reset(int $ttl = 1): self
    {
        reset($this->tokens); $this->skips = []; usleep($ttl); return $this;
    }

    /** @return MarketplaceApiKey[] */
    public function all(): array
    {
        return $this->tokens;
    }

    public function current(): false|MarketplaceApiKey
    {
        return current($this->tokens);
    }

    public function next(): false|MarketplaceApiKey
    {
        return next($this->tokens);
    }

    public function enqueue(string $endpoint, mixed $node = null, string $method = 'get', mixed $post = null, ...$custom): void
    {
        if(!$token = $node instanceof MarketplaceApiKey ? $node : call_user_func($this->handlers[$node ?? 'current'])) return; $token->increment('process');

        $this->manager->queue->enqueue(['as' => $key = (string) Str::uuid()] + $token->encode + [$method => [$endpoint, $post]]);

        $this->attributes[$key] = [compact('endpoint', 'token', 'method', 'post'), ...$custom];
    }

    public function start(): void
    {
        call_user_func($this->handlers['start'] ?? fn() => null); $this->throws = 0;
    }
}
