<?php namespace App\Services\WB;

use App\Exceptions\Http\ErrorException;
use App\Helpers\Arr;
use App\Helpers\Time;
use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\Schedule;
use App\Models\Dev\WB\Sizes;
use App\Services\Sources\Tokens;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Dev\WB\Prices as ModelPrices;

class Prices extends ProductAbstract
{
    /** @var int[] */
    private array $offsets = [];

    private array $missing = [];

    private array $tids = [];

    private const maximum = 300;

    protected function start(): void
    {
        /**
         * @var Model $class
         * @var ModelPrices $price
         */

        if(count($this->missing = Cache::get($this->hash) ?? [])) return;

        foreach([Sizes::class, ModelPrices::class] as $class) $this->updateInstances($class::query());

        while(true)
        {
            $limit = match(count(array_filter($this->offsets, fn($v) => $v !== false)))
            {
                1 => 1000, 2 => 500, 3 => 333, 4 => 250, 5 => 200, default => 100
            };

            foreach($this->manager->source->all('WB') as $tid => $token) if(Arr::get($this->offsets, $tid) !== false) $this->endpoint(Tokens::class, http_build_query(compact('limit') + ['offset' => $this->offsets[$tid] ?? 0]), $token);

            $this->results = []; if(!$this->manager->count()) break;

            $this->manager->init(function(Response $response, $attributes)
            {
                if($response->status() === 429) usleep(1000000); if(!$response->successful()) throw new ErrorException($response); /** @var MarketplaceApiKey $token */ $token = $attributes['token'];

                if(!is_array($products = $response->json('data.listGoods'))) throw new ErrorException($response); if(!count($products)) $this->offsets[$token->id] = false;

                foreach($products as $product)
                {
                    foreach($product['sizes'] as $size) $this->results[$size['sizeID']] = [
                        'sizeID' => $size['sizeID'],
                        'nmID' => $product['nmID'],
                        'tid' => $token->id,
                        'last_request' => date('Y-m-d H:i:s'),
                        'active' => 'Y',
                        'price' => $size['price'],
                        'discountedPrice' => $size['discountedPrice'],
                        'clubDiscountedPrice' => $size['clubDiscountedPrice'],
                        'discount' => $product['discount'],
                        'clubDiscount' => $product['clubDiscount']
                    ];

                    $this->offsets[$token->id] ??= 0; $this->offsets[$token->id]++;
                }
            });

            if(count($this->results)) foreach(array_chunk($this->results, 2000) as $chunk) ModelPrices::shortUpsert($chunk);
        }

        Sizes::query()->whereHas('price', fn(Builder $query) => $query->where('active', 'Y'))->update(['active' => 'Y']);

        Log::channel('wb')->info(implode(' | ', ['WB Prices', ...\Illuminate\Support\Arr::map($this->counts(ModelPrices::class), fn($v, $k) => $k.': '.$v)]));

        foreach(ModelPrices::query()->where('active', 'Y')->whereDoesntHave('sizes')->orderBy('nmID')->get(['nmID', 'tid']) as $price) $this->missing[$price->nmID] ??= $this->tids[$price->tid] ??= $price->tid;

        if(count($this->missing) <= self::maximum) $this->tids = []; else
        {
            Log::channel('wb')->info(implode(' | ', ['WB Have More Then '.self::maximum.' New Products', count($this->missing), ...array_slice(array_keys($this->missing), 0, 5)])); $this->missing = [];
        }
    }

    protected function collect(): void
    {
        /** @var int[] $tids */ $tids = [];

        foreach($this->missing as $nmID => $tid)
        {
            $tids[$tid] ??= 0; if(++$tids[$tid] > 5) continue;

            foreach($this->endpoint(Tokens::class, Products::class) as [$operator, $endpoint, $post])
            {
                if(Arr::get($this->skip, $operator, $tid)) continue; $post['settings']['cursor']['limit'] = 1; $post['settings']['filter']['textSearch'] = strval($nmID);

                $this->manager->enqueue($endpoint, 'WB:'.$tid, 'post', $post, $operator, $tid, fn(array $result): array => $this->controller($result, $nmID));
            }

            if($this->manager->count() >= 30) break;
        }
    }

    private function controller(array $result, $nmID): array
    {
        unset($this->missing[$nmID]); return $result['cards'];
    }

    protected function finish(): bool
    {
        if(count($this->missing))
        {
            Log::channel('wb')->info(implode(' | ', ['WB Products Iteration', $this->operation->counter, count($this->missing), Time::during(time() - $this->start)]));

            Cache::set($this->hash, $this->missing, 1800); return false;
        }

        if($cnt = count($this->tids)) MarketplaceApiKey::query()->where('marketplace', 'WB')->whereNotIn('id', $this->tids)->update(['active' => null]);

        Schedule::shortUpsert([
            ['market' => 'WB', 'operation' => 'PRODUCTS', 'next_start' => $cnt ? time() : strtotime('tomorrow 3:00'), 'counter' => $cnt ? 1 : 0],
            ['market' => 'WB', 'operation' => 'PRICES', 'next_start' => !$cnt && strtotime('today 12:00') > $this->operation->next_start ? strtotime('today 15:00') : null, 'counter' => 0],
            ['market' => 'WB', 'operation' => 'WB_PRICES', 'next_start' => $cnt ? null : time(), 'counter' => 0]
        ]);

        Cache::delete($this->hash); return !$cnt;
    }
}
