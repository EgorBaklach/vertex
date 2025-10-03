<?php namespace App\Services\OZON;

use App\Helpers\Func;
use App\Helpers\String\Cutter;
use App\Helpers\Time;
use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\OZON\{Commissions, Errors, Indexes, Prices as ModelPrices, Products, Statuses};
use App\Models\Dev\Schedule;
use App\Models\Dev\Traits\CustomQueries;
use App\Services\APIManager;
use App\Services\MSAbstract;
use App\Services\Sources\Tokens;
use App\Services\Traits\Queries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class Prices extends MSAbstract
{
    use Queries;

    /** @var int[] */
    private array $last_ids = [];

    private array $prices = [];

    private const classes = [ModelPrices::class, Commissions::class, Indexes::class, Statuses::class];

    public function __invoke(): void
    {
        /** @var Model|CustomQueries $class */ $start = time(); $manager = $this->endpoint(Tokens::class, APIManager::class); $DB = DB::connection('dev');

        foreach(self::classes as $class) $this->updateInstances(match($class)
        {
            ModelPrices::class => $class::query()->whereIn('token_id', array_keys($manager->source->all())), default => $class::query()
        });

        Errors::query()->truncate(); ModelPrices::query()->update(['fbo' => null, 'fbs' => null]);

        $manager->source->throw = function(Throwable $e, $attributes, ...$data) use ($manager)
        {
            $manager->enqueue(...array_values($attributes), ...$data); $attributes['token']->inset('abort');
        };

        while(true)
        {
            /** @var MarketplaceApiKey $token */ $timestamp = floor(microtime(true) * 1000);

            foreach($manager->source->all() as $token)
            {
                if(($last_id = Arr::get($this->last_ids, $token->id)) === false) continue;

                foreach(array_chunk(Products::query()->where('token_id', $token->id)->where('id', '>', $last_id ?: 0)->orderBy('id')->limit(2000)->pluck('id')->all(), 500) as $chunk)
                {
                    $this->endpoint(Tokens::class, $chunk, $token); $this->last_ids[$token->id] = array_pop($chunk);
                }

                if($this->last_ids[$token->id] === $last_id) $this->last_ids[$token->id] = false;
            }

            if(!$manager->count()) break;

            $manager->init(function(Response $response, $attributes)
            {
                /** @var MarketplaceApiKey $token */ $token = $attributes['token'];

                try
                {
                    foreach($response->json('items') as $product)
                    {
                        $product['price_index'] = call_user_func(Cutter::after, 'COLOR_INDEX_', $product['price_indexes']['color_index']);

                        foreach($product['sources'] as $source)
                        {
                            $this->prices[$source['sku']] = [
                                'marketing_price' => (float) strlen($product['marketing_price']) ? (float) $product['marketing_price'] : null,
                                'min_price' => strlen($product['min_price']) ? (float) $product['min_price'] : null,
                                'old_price' => strlen($product['old_price']) ? (float) $product['old_price'] : null,
                                'price' => (float) $product['price']
                            ];

                            $this->results[ModelPrices::class][$source['sku']] = $this->prices[$source['sku']] + [
                                'pid' => $product['id'],
                                'created_at' => date('Y-m-d H:i:s', strtotime($source['created_at'])),
                                'last_request' => date('Y-m-d H:i:s'),
                                'active' => 'Y',
                                'token_id' => $token->id,
                                'sku' => $source['sku'],
                                'vat' => Func::call((float) $product['price'], fn($value) => $value ?: null),
                                'price_index' => $product['price_index'],
                                'visible_by_price' => $product['visibility_details']['has_price'] ? 'Y' : null,
                                'visible_by_stock' => $product['visibility_details']['has_stock'] ? 'Y' : null,
                                'source' => $source['source'],
                                'shipment_type' => call_user_func(Cutter::after, 'SHIPMENT_TYPE_', $source['shipment_type']),
                                'fbo' => null,
                                'fbs' => null,
                            ];
                        }

                        foreach($product['stocks']['stocks'] ?? [] as $stock) if($stock['present'] || $stock['reserved']) $this->results[ModelPrices::class][$stock['sku']][$stock['source']] = implode(' \ ', [$stock['present'], $stock['reserved']]);

                        foreach($product['commissions'] as $commission) $this->results[Commissions::class][implode(' | ', [$product['id'], $commission['sale_schema']])] = [
                            'pid' => $product['id'],
                            'last_request' => date('Y-m-d H:i:s'),
                            'active' => 'Y',
                            'delivery_amount' => $commission['delivery_amount'] ?? null,
                            'percent' => $commission['percent'] ?? null,
                            'return_amount' => $commission['return_amount'] ?? null,
                            'sale_schema' => $commission['sale_schema'],
                            'value' => $commission['value']
                        ];

                        foreach($product['errors'] as $error) $this->results[Errors::class][md5(json_encode($error))] ??= [
                            'product_id' => $product['id'],
                            'property_id' => $error['attribute_id'] ?: null,
                            'code' => $error['code'] ?? null,
                            'field' => strlen($error['field']) ? $error['field'] : null,
                            'level' => call_user_func(Cutter::after, 'ERROR_LEVEL_', $error['level']),
                            'state' => $error['state'],
                            'description' => strlen($error['texts']['description'] ?? null) ? $error['texts']['description'] : null,
                            'message' => strlen($error['texts']['message'] ?? null) ? $error['texts']['message'] : null,
                            'params' => is_array($error['texts']['params'] ?? null) && count($error['texts']['params']) ? json_encode($error['texts']['params']) : null
                        ];

                        if(!in_array($product['price_index'], ['UNSPECIFIED', 'WITHOUT_INDEX']))
                        {
                            foreach(['external', 'ozon', 'self_marketplaces'] as $state)
                            {
                                if($product['price_indexes'][$state.'_index_data']['price_index_value'])
                                {
                                    $this->results[Indexes::class][implode(' | ', [$product['id'], $state])] = [
                                        'pid' => $product['id'],
                                        'type' => $state,
                                        'last_request' => date('Y-m-d H:i:s'),
                                        'active' => 'Y',
                                        'minimal_price' => (float) $product['price_indexes'][$state.'_index_data']['minimal_price'],
                                        'price_index_value' => $product['price_indexes'][$state.'_index_data']['price_index_value']
                                    ];
                                }
                            }
                        }

                        $this->results[Statuses::class][$product['id']] = [
                            'pid' => $product['id'],
                            'updated_at' => date('Y-m-d H:i:s', strtotime($product['statuses']['status_updated_at'])),
                            'last_request' => date('Y-m-d H:i:s'),
                            'active' => 'Y',
                            'is_created' => $product['statuses']['is_created'] ? 'Y' : null,
                            'moderate_status' => strlen($product['statuses']['moderate_status']) ? $product['statuses']['moderate_status'] : null,
                            'status' => $product['statuses']['status'],
                            'status_description' => strlen($product['statuses']['status_description']) ? $product['statuses']['status_description'] : null,
                            'status_failed' => strlen($product['statuses']['status_failed']) ? $product['statuses']['status_failed'] : null,
                            'status_name' => strlen($product['statuses']['status_name']) ? $product['statuses']['status_name'] : null,
                            'status_tooltip' => strlen($product['statuses']['status_tooltip']) ? $product['statuses']['status_tooltip'] : null,
                            'validation_status' => strlen($product['statuses']['validation_status']) ? $product['statuses']['validation_status'] : null
                        ];
                    }
                }
                catch (Throwable $e)
                {
                    Log::channel('error')->error(['OZON Prices', $response->body(), $e->getMessage()]); throw $e;
                }
            });

            foreach(self::classes as $class) foreach(array_chunk($this->results[$class] ?? [], 2000) as $chunk) $class::shortUpsert($chunk);

            if(count($this->results[Errors::class] ?? []))
            {
                $DB->statement('SET FOREIGN_KEY_CHECKS=0;'); foreach(array_chunk($this->results[Errors::class], 2000) as $chunk) Errors::query()->insert($chunk); $DB->statement('SET FOREIGN_KEY_CHECKS=1;');
            }

            $this->results = []; if(floor(microtime(true) * 1000) - $timestamp <= 500) sleep(1);
        }

        //Log::channel('ozon')->info('OZON Prices | Time to keep history');

        //foreach($this->prices as $sku => $price) Storage::disk('local')->append('history/ozon/'.$sku.'/price.csv', implode(' | ', [date('Y-m-d H:i:s'), ...array_values(Arr::map($price, fn($v, $k) => $k.': '.$v))]));

        Log::channel('ozon')->info(implode(' | ', ['RESULTS', Time::during(time() - $start)]));

        foreach(self::classes as $class) Log::channel('ozon')->info(implode(' | ', ['OZON '.call_user_func(fn($class) => $class, ...array_reverse(explode('\\', $class))), ...Arr::map($this->counts($class), fn($v, $k) => $k.': '.$v)]));

        Log::channel('ozon')->info(implode(' | ', ['OZON Errors', Errors::query()->count()]));

        Schedule::shortUpsert([
            ['market' => 'OZON', 'operation' => 'PRICES', 'next_start' => $this->operation->start > strtotime('today 12:00') ? null : strtotime('today 17:00'), 'counter' => 0],
            ['market' => 'OZON', 'operation' => 'FBS_STOCKS', 'next_start' => $this->operation->start > strtotime('today 12:00') ? null : time(), 'counter' => 0],
        ]);
    }
}
