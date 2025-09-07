<?php namespace App\Services\WB;

use App\Exceptions\Http\ErrorException;
use App\Exceptions\Http\HttpAbstract;
use App\Helpers\Time;
use App\Models\Dev\WB\Prices as ModelPrices;
use App\Models\Dev\WB\Sizes;
use App\Services\APIManager;
use App\Services\MSAbstract;
use App\Services\Sources\Proxies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class WBPrices extends MSAbstract
{
    /** @var array[] */
    private array $chunks = [];

    /** @var string[] */
    private array $repeats = [];

    private const limit = 5;

    private bool $skip = false;

    private function repeat($uuid): bool
    {
        $this->repeats[$uuid] ??= $this->repeats[$uuid] ?? 0; usleep(1000000); return ++$this->repeats[$uuid] <= self::limit;
    }

    public function __invoke(): void
    {
        $start = time(); $manager = $this->endpoint(Proxies::class, APIManager::class);

        $manager->source->abort = function (HttpAbstract $e)
        {
            Log::channel('error')->error(['WBPrices: '.$e->response->status(), $e->response->body(), $e->endpoint]); $this->skip = true;
        };

        $manager->source->throw = function(Throwable $e, $attributes, $uuid, $key) use ($manager)
        {
            Log::channel('error')->error('WBPrices | '.$e); if($this->repeat($uuid)) $manager->source->enqueue($attributes['endpoint'], null, $attributes['method'], $attributes['post'], $uuid, $key); else $this->skip = true;
        };

        $this->chunks = Cache::remember($this->hash, 600, function()
        {
            $query = ModelPrices::query()
                ->whereHas('token', fn(Builder $b) => $b->whereJsonContains('days', date('N') * 1))
                ->whereHas('product', fn(Builder $builder) => $builder->where('active', 'Y'))
                ->where(function(Builder $query)
                {
                    foreach(['sizes.fbs_amounts' => 'whereHas', 'sizes.fbo_amounts' => 'orWhereHas'] as $relation => $method)
                        $query = $query->{$method}($relation, fn(Builder $builder) => $builder->where('amount', '>', 0));
                });

            $rows = []; $last_id = 0;

            foreach($query->distinct('nmID')->orderBy('nmID')->pluck('nmID')->all() as $id)
            {
                $rows[] = $last_id = $id; if(count($rows) >= 300): $this->chunks[$last_id] = implode(';', $rows); $rows = []; endif;
            }

            if(count($rows)) $this->chunks[$last_id] = implode(';', $rows); return $this->chunks;
        });

        foreach($this->chunks as $key => $chunk)
        {
            $this->endpoint(Proxies::class, $chunk, $key); if($manager->count() >= 5) $this->iterate($manager); if($this->skip) break;
        }

        if($manager->count()) $this->iterate($manager);

        if(count($this->chunks))
        {
            Cache::set($this->hash, $this->chunks, 600); Log::channel('wb')->info(implode(' | ', ['WB WBPrices Iteration', $this->operation->counter, count($this->chunks), Time::during(time() - $start)]));
        }
        else
        {
            Cache::delete($this->hash); $fields = ['price', 'wbPrice', 'discountedPrice', 'clubDiscountedPrice', 'discount', 'clubDiscount']; /** @var Sizes $size */

            Log::channel('wb')->info('WB WBPrices | Time to keep history');

            foreach(Sizes::query()->where('active', 'Y')->whereHas('price', fn(Builder $builder) => $builder->where('active', 'Y')->where('price', '>', 0))->distinct('sku')->get() as $size)
            {
                Storage::disk('local')->append('history/wb/'.$size->sku.'/price.csv', implode(' | ', [date('Y-m-d H:i:s'), ...array_map(fn($v) => $v.': '.($size->price->{$v} ?? 'null'), $fields)]));
            }

            Log::channel('wb')->info(implode(' | ', ['RESULT', Time::during(time() - $start)]));
            Log::channel('wb')->info(implode(' | ', ['WB WBPrices', ModelPrices::query()->whereNotNull('wbPrice')->count()]));

            $this->operation->update(['next_start' => null, 'counter' => 0]);
        }
    }

    private function iterate(APIManager $manager): void
    {
        $manager->init(function(Response $response, $attributes, $uuid, $key)
        {
            if($this->skip || !is_array($products = $response->json('products'))) throw new ErrorException($response); unset($this->chunks[$key]);

            foreach($products as $product)
            {
                //if(!count($product['sizes'] ?? [])) Log::channel('error')->error(['WBPrices | Empty Sizes', json_encode($product)]);

                foreach ($product['sizes'] ?? [] as $size) if($price = Arr::get($size, 'price.product')) $this->results[$size['optionId']] = [
                    'sizeID' => $size['optionId'],
                    'wbPrice' => round($price / 100)
                ];
            };

            if(!(count($this->chunks) % 100)) Log::channel('wb')->info(implode(' | ', ['WB WBPrices chunks', count($this->chunks)]));
        });

        if(count($this->results)) ModelPrices::query()->upsert($this->results, [], ['sizeID', 'wbPrice']); $this->results = [];
    }
}
