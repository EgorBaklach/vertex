<?php namespace App\Services\OZON;

use App\Exceptions\Http\SuccessException;
use App\Helpers\Arr;
use App\Helpers\Func;
use App\Helpers\Time;
use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\Schedule;
use App\Services\MSAbstract;
use App\Models\Dev\OZON\{CT, Files, PPV, Products as ModelProducts, Properties, PV, Types};
use App\Services\APIManager;
use App\Services\Sources\Tokens;
use App\Services\Traits\Queries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @method null|Properties properties(int $id)
 * @method null|int cts(int $cid, int $pid)
 *
 * @property callable list
 * @property callable attributes
 */
class Products extends MSAbstract
{
    use Queries;

    private array $entities = [];

    /** @var string[]|bool[] */
    private array $cursors = [];

    protected const limit = 20;

    const fields = [
        'id' => 'attributes',
        'last_request' => 'attributes',
        'active' => 'attributes',
        'offer_id' => 'attributes',
        'barcode' => 'attributes',
        'token_id' => 'attributes',
        'cid' => 'attributes',
        'tid' => 'attributes',
        'name' => 'attributes',
        'dimensions' => 'attributes',
        'dimension_unit' => 'attributes',
        'weight' => 'attributes',
        'weight_unit' => 'attributes',
        'model_id' => 'attributes',
        'model_count' => 'attributes',
        'color_image' => 'attributes',

        'archived' => 'list',
        'has_fbo_stocks' => 'list',
        'has_fbs_stocks' => 'list',
        'is_discounted' => 'list',
        'quants' => 'list'
    ];

    public function __get(string $name): callable
    {
        return function(array $product) use ($name): array
        {
            foreach(self::fields as $field => $node) if($name !== $node) $product[$field] = $this->results[ModelProducts::class][$product['id']][$field] ?? $product[$field]; return $product;
        };
    }

    public function __call(string $name, array $arguments)
    {
        return Arr::get($this->entities[$name], ...$arguments);
    }

    private function list(array $response, int $token_id): ?string
    {
        foreach($response['result']['items'] ?? [] as $product)
        {
            $this->results[ModelProducts::class][$product['product_id']] = call_user_func($this->list, array_combine(array_keys(self::fields), [
                $product['product_id'], date('Y-m-d H:i:s'), null, $product['offer_id'], null, $token_id,
                null, null, null, null, null, null, null, null, null, null,

                $product['archived'] ? 'Y' : null,
                $product['has_fbo_stocks'] ? 'Y' : null,
                $product['has_fbo_stocks'] ? 'Y' : null,
                $product['is_discounted'] ? 'Y' : null,
                count($product['quants']) ? json_encode($product['quants']) : null
            ]));
        }

        return Arr::get($response, 'result', 'last_id');
    }

    private function attributes(array $response, int $token_id): ?string
    {
        foreach($response['result'] ?? [] as $product)
        {
            // Может логировать товары у которых некорректные категория или тип товара?

            if(!$this->cts($product['description_category_id'], $product['type_id'])) continue;

            $this->results[Types::class][$product['type_id']] ??= ['id' => $product['type_id'], 'cnt' => 0]; $this->results[Types::class][$product['type_id']]['cnt']++;

            $this->results[ModelProducts::class][$product['id']] = call_user_func($this->attributes, array_combine(array_keys(self::fields), [
                $product['id'],
                date('Y-m-d H:i:s'),
                'Y',
                $product['offer_id'],
                strlen($product['barcode']) ? $product['barcode'] : null,
                $token_id,
                $product['description_category_id'],
                $product['type_id'],
                $product['name'] ?? null,
                call_user_func(fn(array $ar) => count($ar) ? implode(':', $ar) : null, Arr::map(['depth', 'width', 'height'], fn($v) => $product[$v] ?? '')),
                $product['dimension_unit'] ?? null,
                $product['weight'] ?? null,
                $product['weight_unit'] ?? null,
                $product['model_info']['model_id'] ?? null,
                $product['model_info']['count'] ?? null,
                strlen($product['color_image']) ? $product['color_image'] : null,

                null, null, null, null, null
            ]));

            foreach([$product['primary_image'], ...$product['images']] as $url) if(strlen($url)) $this->results[Files::class][md5($url)] ??= [
                'pid' => $product['id'],
                'last_request' => date('Y-m-d H:i:s'),
                'type' => 'picture',
                'url' => $url,
                'caption' => null
            ];

            foreach($product['pdf_list'] as $pdf) $this->results[Files::class][md5($pdf['file_name'])] ??= [
                'pid' => $product['id'],
                'last_request' => date('Y-m-d H:i:s'),
                'type' => 'pdf',
                'url' => $pdf['file_name'],
                'caption' => strlen($pdf['name']) ? $pdf['name'] : null
            ];

            foreach(['attributes', 'complex_attributes'] as $state)
            {
                foreach($product[$state] ?? [] as $property)
                {
                    // Аналогично, при отсутствии корректной характеристики, нужно ли ее также логировать?

                    if(!$this->properties($property['id'])) continue;

                    foreach($property['values'] as $value)
                    {
                        $value['value'] = preg_replace('/[\n\r]+/', '', $value['value']);

                        if($value['dictionary_value_id'])
                        {
                            if(!$this->properties($property['id'])->did) continue 2;

                            $this->results[PV::class][$value['dictionary_value_id']] ??= [
                                'id' => $value['dictionary_value_id'],
                                'did' => $this->properties($property['id'])->did,
                                'last_request' => date('Y-m-d H:i:s'),
                                'active' => 'Y',
                                'value' => $value['value'],
                                'info' => null,
                                'picture' => null
                            ];
                        }

                        $this->results[PPV::class][implode(' | ', [$product['id'], $property['id'], $value['dictionary_value_id'] ?: $value['value']])] = [
                            'product_id' => $product['id'],
                            'property_id' => $property['id'],
                            'pvid' => $value['dictionary_value_id'],
                            'value' => !$value['dictionary_value_id'] ? $value['value'] : null,
                            'is_complex' => match ($state)
                            {
                                'complex_attributes' => 'Y',
                                'attributes' => null
                            },
                            'complex_id' => $property['complex_id'] ?: null
                        ];
                    }
                }
            }
        }

        return Arr::get($response, 'last_id');
    }

    public function __invoke(): void
    {
        /** @var Model $class */ $start = time(); $manager = $this->endpoint(Tokens::class, APIManager::class);

        $this->updateInstances(ModelProducts::query()->whereIn('tid', array_keys($manager->source->all()))); PPV::query()->truncate(); Types::query()->update(['cnt' => 0]);

        foreach(Properties::query()->where('active', 'Y')->get(['id', 'did']) as $property) $this->entities['properties'][$property->id] = $property;

        foreach(CT::query()->get() as $ct) $this->entities['cts'][$ct->cid][$ct->tid] = $ct->tid;

        $manager->source->throw = function(Throwable $e, $attributes, ...$data) use ($manager)
        {
            $manager->enqueue(...array_values($attributes), ...$data); $attributes['token']->inset('abort');
        };

        while(true)
        {
            $timestamp = floor(microtime(true) * 1000);

            foreach($this->endpoint(Tokens::class) as [$operator, $endpoint, $post])
            {
                foreach(['ALL', 'ARCHIVED'] as $visibility)
                {
                    foreach($manager->source->all() as $tid => $token)
                    {
                        if(($post['last_id'] = $this->cursors[$visibility][$tid] ?? '') === false) continue;

                        $manager->enqueue($endpoint, $token, 'post', ['limit' => 1000, 'filter' => compact('visibility')] + $post, $visibility, $operator);
                    }
                }
            }

            $manager->init(function(Response $response, $attributes, $visibility, $operator)
            {
                /** @var MarketplaceApiKey $token */ $token = $attributes['token']; if(Arr::get($this->cursors, $visibility, $token->id) === false) throw new SuccessException($response);

                try
                {
                    $this->cursors[$visibility][$token->id] = Func::call($this->{$operator}($response->json(), $token->id), fn(?string $last_id) => $last_id ?: false);
                }
                catch (Throwable $e)
                {
                    Log::channel('error')->error(['OZON Products', $response->body(), $e->getMessage()]); throw $e;
                }
            });

            if(is_null(Arr::get($this->results, ModelProducts::class))) break;

            foreach([ModelProducts::class, PV::class, PPV::class, Files::class, Types::class] as $class) foreach(array_chunk($this->results[$class] ?? [], 2000) as $chunk)
            {
                $class::query()->upsert($chunk, [], match($class)
                {
                    ModelProducts::class => call_user_func(function()
                    {
                        $fields = []; foreach(self::fields as $field => $node) $fields[$node][] = $field; return $this->keep(...$fields['attributes']) + $fields['list'];
                    }),
                    PV::class => ['last_request'] + $this->replace('info', 'picture'), // last_request = values(`last_requst`), info = CASE WHEN ...,  picture = CASE WHEN ...
                    default => null
                });
            }

            $this->results = []; if(floor(microtime(true) * 1000) - $timestamp <= 500) sleep(1);
        }

        Log::channel('ozon')->info(implode(' | ', ['RESULTS', Time::during(time() - $start)]));
        Log::channel('ozon')->info(implode(' | ', ['OZON Products', ...\Illuminate\Support\Arr::map($this->counts(ModelProducts::class), fn($v, $k) => $k.': '.$v)]));

        Schedule::shortUpsert([
            ['market' => 'OZON', 'operation' => 'PRODUCTS', 'next_start' => strtotime('tomorrow 6:00'), 'counter' => 0],
            ['market' => 'OZON', 'operation' => 'PRICES', 'next_start' => time(), 'counter' => 0]
        ]);
    }
}
