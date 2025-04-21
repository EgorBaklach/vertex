<?php namespace App\Services\OZON;

use App\Exceptions\Http\ErrorException;
use App\Exceptions\Http\HttpAbstract;
use App\Services\APIManager;
use App\Services\MSAbstract;
use App\Services\Sources\Tokens;
use App\Services\Traits\Repeater;
use ErrorException as NativeErrorException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class TokensAbstract extends MSAbstract
{
    use Repeater;

    protected int $counter = -1;

    protected int $start;

    protected const limit = 10;

    public function __invoke(): void
    {
        $this->start = time(); $manager = $this->endpoint(Tokens::class, APIManager::class); $manager->source->handlers['next'] = fn() => $manager->source->next('OZON') ?: $this->commit($manager);

        foreach($this->collect() as $node) $this->enqueue($node); while($manager->count()) $this->commit($manager); $this->finish();
    }

    protected function commit(APIManager $manager)
    {
        $manager->source->reset('OZON', 250000); $this->counter = -1;

        $manager->init(function(Response $response, $attributes, ...$custom)
        {
            try
            {
                if(!$response->successful() && $response->json('code') === 3) throw new ErrorException($response); $this->pull($response, $attributes, fn($callback) => $callback(...$custom));
            }
            catch (Throwable $e)
            {
                Log::channel('error')->error(implode(' | ', ['OZON '.$this->operation->operation.': '.$response->status(), $response->body()])); if($e instanceof HttpAbstract) throw $e;

                throw $this->isAccess($attributes['token']) ? new NativeErrorException : new ErrorException($response);
            }
        });

        $this->commitAfter(); return $manager->source->{$this->index()}('OZON');
    }

    protected function enqueue(...$values): void
    {
        $this->endpoint(Tokens::class, $this->index(), ...$values);
    }

    private function index(): string
    {
        return !++$this->counter || $this->counter % self::limit ? 'current' : 'next';
    }

    protected function reset(): void
    {
        $this->repeats = [];
    }

    abstract protected function collect(): mixed;

    abstract protected function pull(Response $response, array $attributes, callable $callback): void;

    abstract protected function commitAfter(): void;

    abstract protected function finish(): void;
}
