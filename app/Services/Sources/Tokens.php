<?php namespace App\Services\Sources;

use App\Exceptions\Http\{HttpAbstract, RepeatException};
use App\Helpers\Arr;
use App\Models\Dev\MarketplaceApiKey;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class Tokens extends SourceAbstract
{
    private array $tokens = [];

    private int $throws = 0;

    public function __construct(MarketplaceApiKey $model)
    {
        foreach($model->where('active', 'Y')->orderBy('last_request')->get(['id', 'marketplace', 'token', 'params']) as $token) $this->tokens[$token->marketplace][$token->id] = $token;

        $this->skip = fn(array $attributes) => $attributes['token']->id; foreach(['success', 'abort'] as $state) $this->{$state} = fn(HttpAbstract $e) => $e->token->inset($state);

        foreach(['current', 'next'] as $operation) $this->handlers[$operation] = fn(string $market) => $this->{$operation}($market);

        $this->repeat = function(HttpAbstract $e)
        {
            $this->enqueue($e->endpoint, $e->token->marketplace.':'.($this->throws++ === 0 ? 'next' : 'current'), $e->method, $e->post, ...$e->custom);

            $this->skips[$e->token->id] = RepeatException::class; $e->token->inset('abort');
        };

        $this->throw = function(Throwable $e, $attributes, ...$data)
        {
            Log::channel('error')->error('Tokens Error | '.$e); $this->enqueue(...array_values($attributes), ...$data); $attributes['token']->inset('abort');
        };
    }

    /** @return MarketplaceApiKey[] */
    public function all(string $market): array
    {
        return $this->tokens[$market];
    }

    public function reset(string $market, int $ttl = 1): self
    {
        reset($this->tokens[$market]); $this->skips = []; usleep($ttl); return $this;
    }

    /** @return MarketplaceApiKey|bool|null */
    public function current(string $market): mixed
    {
        return current($this->tokens[$market]);
    }

    /** @return MarketplaceApiKey|bool|null */
    public function next(string $market): mixed
    {
        return next($this->tokens[$market]);
    }

    /** @return MarketplaceApiKey|bool|null */
    public function token(string $market, mixed $node = 'current'): mixed
    {
        return Arr::get($this->tokens, $market, $node) ?? call_user_func($this->{$node}, $market);
    }

    public function enqueue(string $endpoint, $data = null, string $method = 'get', mixed $post = null, ...$custom): void
    {
        if(is_null($data) || !$token = $data instanceof MarketplaceApiKey ? $data : $this->token(...explode(':', $data))) return; $token->increment('process');

        $this->queue->enqueue(['as' => $key = (string) Str::uuid()] + $token->encode + [$method => [$endpoint, $post]]);

        $this->attributes[$key] = [compact('endpoint', 'token', 'method', 'post'), ...$custom];
    }

    public function start(): void
    {
        $this->throws = 0;
    }
}
