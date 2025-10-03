<?php namespace App\Services;

use App\Helpers\Func;
use App\Services\Sources\SourceInterface;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SplQueue;
use Throwable;

readonly class APIManager
{
    public SplQueue $queue;

    public function __construct(public SourceInterface $source)
    {
        $this->queue = new SplQueue; $this->source->init($this);
    }

    public function enqueue(...$values): self
    {
        $this->source->enqueue(...$values); return $this;
    }

    public function count(): int
    {
        return $this->queue->count();
    }

    public function init(callable $controller): ?bool
    {
        $this->source->start(); if($this->queue->isEmpty()) return false;

        try
        {
            $this->source->exec($controller, Http::pool(function (Pool $pool)
            {
                while(!$this->queue->isEmpty())
                {
                    Func::call($pool, function(Pool $pool)
                    {
                        foreach($this->queue->dequeue() as $method => $params)
                        {
                            foreach(['Before' => $pool, 'After' => $pool = $pool->{$method}(...(array) $params)] as $state => $node)
                            {
                                $params = call_user_func($this->source->handlers[$method.$state] ?? fn() => null, $node, ...(array) $params) ?? $params;
                            }
                        }
                    });
                }
            }));
        }
        catch (Throwable $e)
        {
            Log::channel('error')->error('APIManager Error | '.$e);
        }

        if(!$this->queue->isEmpty()) return $this->init($controller); $this->source->finish(); return true;
    }

    public function __toString(): string
    {
        return self::class;
    }
}
