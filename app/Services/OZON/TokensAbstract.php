<?php namespace App\Services\OZON;

use App\Exceptions\Http\ErrorException;
use App\Exceptions\Http\HttpAbstract;
use App\Services\APIManager;
use App\Services\MSAbstract;
use App\Services\Sources\Tokens;
use ErrorException as NativeErrorException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class TokensAbstract extends MSAbstract
{
    protected int $counter = -1;

    protected int $start;

    protected const limit = 5;

    public function __invoke(): void
    {
        $this->start = time(); $manager = $this->endpoint(Tokens::class, APIManager::class); $manager->source->handlers['next'] = fn() => $manager->source->next() ?: $this->commit($manager);

        $manager->source->throw = function(Throwable $e, $attributes, ...$data)
        {
            $this->enqueue(...array_values($attributes), ...$data); $attributes['token']->inset('abort');
        };

        foreach($this->collect() as $node) $this->enqueue($node); while($manager->count()) $this->commit($manager); $this->finish();
    }

    protected function commit(APIManager $manager)
    {
        $manager->source->reset(); $this->counter = 0;

        $manager->init(function(Response $response, $attributes, ...$custom)
        {
            try
            {
                if(!$response->successful() && $response->json('code') === 3) throw new ErrorException($response); $this->pull($response, $attributes, fn($callback) => $callback(...$custom));
            }
            catch (Throwable $e)
            {
                Log::channel('error')->error(implode(' | ', ['OZON '.$this->operation->operation.': '.$response->status(), print_r($custom, true), $response->body()]));

                throw $e instanceof HttpAbstract ? $e : new NativeErrorException;
            }
        });

        $this->commitAfter(); return $manager->source->current();
    }

    protected function enqueue(...$values): void
    {
        $this->endpoint(Tokens::class, ++$this->counter && $this->counter % static::limit === 0 ? 'next' : 'current', ...$values);
    }

    abstract protected function collect(): mixed;

    abstract protected function pull(Response $response, array $attributes, callable $callback): void;

    abstract protected function commitAfter(): void;

    abstract protected function finish(): void;
}
