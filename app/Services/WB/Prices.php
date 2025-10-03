<?php namespace App\Services\WB;

use App\Exceptions\Http\ErrorException;
use App\Helpers\Arr;
use App\Helpers\Time;
use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\Schedule;
use App\Models\Dev\WB\Sizes;
use App\Services\APIManager;
use App\Services\MSAbstract;
use App\Services\Sources\Tokens;
use App\Services\Traits\Queries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Dev\WB\Prices as ModelPrices;

class Prices extends MSAbstract
{
    use Queries;

    /** @var int[] */
    private array $offsets = [];

    public function __invoke(): void
    {
        /** @var string|Model $class */

        $start = time(); $manager = $this->endpoint(Tokens::class, APIManager::class); $DB = DB::connection('dev');

        foreach([Sizes::class, ModelPrices::class] as $class)
        {
            $this->updateInstances($class::query()->whereIn('tid', array_keys($manager->source->all())));
        }

        $DB->statement('SET FOREIGN_KEY_CHECKS=0;');

        while(true)
        {
            /** @var MarketplaceApiKey $token */

            $limit = match(count(array_filter($this->offsets, fn($v) => $v !== false)))
            {
                1 => 1000, 2 => 500, 3 => 333, 4 => 250, 5 => 200, default => 100
            };

            foreach($manager->source->all() as $tid => $token)
            {
                if(Arr::get($this->offsets, $tid) !== false)
                {
                    $this->endpoint(Tokens::class, http_build_query(compact('limit') + ['offset' => $this->offsets[$tid] ??= 0]), $token);
                }
            }

            if(!$manager->count()) break;

            $manager->init(function(Response $response, $attributes)
            {
                if(!$response->successful()) throw new ErrorException($response); /** @var MarketplaceApiKey $token */ $token = $attributes['token'];

                if(!is_array($products = $response->json('data.listGoods'))) throw new ErrorException($response); if(!count($products)) $this->offsets[$token->id] = false;

                foreach($products as $product)
                {
                    foreach($product['sizes'] as $size)
                    {
                        $this->results[$size['sizeID']] = [
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
                    }

                    $this->offsets[$token->id]++;
                }
            });

            if(count($this->results)) foreach(array_chunk($this->results, 2000) as $chunk) ModelPrices::shortUpsert($chunk); $this->results = [];
        }

        $DB->statement('SET FOREIGN_KEY_CHECKS=1;'); Sizes::query()->whereHas('price', fn(Builder $query) => $query->where('active', 'Y'))->update(['active' => 'Y']);

        Log::channel('wb')->info(implode(' | ', ['RESULTS', Time::during(time() - $start)]));
        Log::channel('wb')->info(implode(' | ', ['WB Prices', ...Arr::map($this->counts(ModelPrices::class), fn($v, $k) => $k.': '.$v)]));

        foreach(ModelPrices::query()->where('active', 'Y')->whereDoesntHave('sizes')->groupBy('tid')->with('token')->select('tid', DB::raw('count(*) as cnt'))->get() as $value)
        {
            Log::channel('wb')->info(implode(' | ', ['WB New Products', $value->token->name, $value->cnt]));
        }

        Schedule::shortUpsert([
            ['market' => 'WB', 'operation' => 'PRICES', 'next_start' => strtotime('today 12:00') > $this->operation->start ? strtotime('today 15:00') : null, 'counter' => 0],
            ['market' => 'WB', 'operation' => 'WB_PRICES', 'next_start' => time(), 'counter' => 0]
        ]);
    }
}