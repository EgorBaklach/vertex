<?php namespace App\Services\Sources;

use App\Helpers\Arr;
use App\Services\APIManager;
use App\Exceptions\Http\{ErrorException, RepeatException, SuccessException};
use Closure;
use ErrorException as NativeErrorException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Throwable;

abstract class SourceAbstract implements SourceInterface
{
    protected APIManager $manager;

    protected array $attributes = [];

    /** @var string[] */
    public array $skips = [];

    public array $ttl = [
        'connectTimeout' => 15,
        'timeout' => 21
    ];

    public Closure $success;
    public Closure $repeat;
    public Closure $abort;
    public Closure $throw;
    public Closure $skip;

    /** @var callable[] */
    public array $handlers = [];

    public function init(APIManager $manager): void
    {
        $this->manager = $manager;

        $this->handlers['asAfter'] = function(PendingRequest $request)
        {
            foreach($this->ttl as $method => $value) $request->{$method}($value);
        };
    }

    /** @param Response[] $responses */
    public function exec(callable $controller, array $responses): void
    {
        foreach($responses as $key => $response)
        {
            $values = $this->attributes[$key]; unset($this->attributes[$key]);

            try
            {
                if($exception = Arr::get($this->skips, call_user_func($this->skip, ...$values))) throw new $exception($response);

                if(!$response instanceof Response) throw $response instanceof Throwable ? $response : new NativeErrorException((string) $response, 500);

                $controller($response, ...$values); throw new SuccessException($response);
            }
            catch (Throwable $e)
            {
                match (get_class($e))
                {
                    SuccessException::class => call_user_func($this->success, $e->values(...$values)),
                    RepeatException::class => call_user_func($this->repeat, $e->values(...$values)),
                    ErrorException::class => call_user_func($this->abort, $e->values(...$values)),
                    default => call_user_func($this->throw, $e, ...$values)
                };
            }
        }
    }

    public function start(): void
    {

    }

    public function finish(): void
    {

    }
}
