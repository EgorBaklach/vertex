<?php namespace App\Services\Sources;

use App\Exceptions\Http\{HttpAbstract, RepeatException};
use App\Helpers\Arr;
use App\Models\Dev\Agents;
use App\Models\Dev\Proxies as ModelProxies;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Str;
use Throwable;

class Proxies extends SourceAbstract
{
    private array $proxies = [];

    private array $agents = [];

    public string $ptype = 'mobile';

    public string $utype = 'mobile';

    public function __construct(ModelProxies $proxies, Agents $agents)
    {
        foreach($proxies->whereLike('active', 'Y')->orderBy('last_request')->get(['id', 'ip', 'port', 'user', 'pass', 'type']) as $proxy) /** @var ModelProxies $proxy */ $this->proxies[$proxy->type][] = $proxy;

        $this->skip = fn(array $attributes) => $attributes['proxy'] instanceof ModelProxies ? $attributes['proxy']->id : null;

        foreach($agents->all() as $agent) /** @var Agents $agent */ $this->agents[$agent->type][] = $agent;

        foreach(['success', 'abort'] as $state) $this->{$state} = fn(HttpAbstract $e) => $e->proxy instanceof ModelProxies && $e->proxy->inset($state);

        $this->repeat = function(HttpAbstract $e)
        {
            $this->enqueue($e->endpoint, null, $e->method, $e->post, ...$e->custom); if($e->proxy) $this->skips[$e->proxy->id] = RepeatException::class; call_user_func($this->abort, $e);
        };

        $this->throw = function(Throwable $e, $attributes, ...$data)
        {
            $this->enqueue($attributes['endpoint'], null, $attributes['method'], $attributes['post'], ...$data);

            $attributes['proxy'] instanceOf ModelProxies && $attributes['proxy']->inset('abort');
        };

        $this->handlers['withOptionsBefore'] = fn(PendingRequest $request, ModelProxies $proxy) => [['proxy' => $proxy->address]];
    }

    private function rand(string $name, ...$values): mixed
    {
        $values[] = mt_rand(0, count(Arr::get($this->{$name}, ...$values)) - 1); return Arr::get($this->{$name}, ...$values);
    }

    public function enqueue(string $endpoint, $data = null, string $method = 'get', mixed $post = null, ...$custom): void
    {
        /**
         * @var ModelProxies|null $proxy
         * @var Agents $agent
         */

        [$proxy, $agent] = match(gettype($data))
        {
            'array' => $data, 'NULL' => [count($this->proxies) ? $this->rand('proxies', $this->ptype) : null, $this->rand('agents', $this->utype)],
            'object' => match(get_class($data))
            {
                ModelProxies::class => [$data, $this->rand('agents', $this->utype)],
                Agents::class => [$this->rand('proxies', $this->ptype), $data]
            },
        };

        $this->queue->enqueue(array_filter(['as' => $key = (string) Str::uuid(), 'withUserAgent' => $agent->name, 'withOptions' => $proxy, $method => [$endpoint, $post]], 'boolval'));

        $this->attributes[$key] = [compact('endpoint', 'proxy', 'agent', 'method', 'post'), ...$custom]; $proxy instanceof ModelProxies && $proxy->increment('process');

    }
}
