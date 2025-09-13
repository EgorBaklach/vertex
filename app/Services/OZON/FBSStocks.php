<?php namespace App\Services\OZON;

use App\Exceptions\Http\ErrorException;
use App\Helpers\Time;
use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\OZON\{FBSAmounts, FBSStocks as ModelFBSStocks, Prices};
use App\Models\Dev\Traits\CustomQueries;
use App\Services\APIManager;
use App\Services\MSAbstract;
use App\Services\Sources\Tokens;
use App\Services\Traits\{Queries, Repeater};
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class FBSStocks extends MSAbstract
{
    use Queries, Repeater;

    /** @var int[] */
    private array $last_skus = [];

    protected const limit = 10;

    public function __invoke(): void
    {
        /** @var CustomQueries $class */ $start = time(); $manager = $this->endpoint(Tokens::class, APIManager::class);

        $this->updateInstances(ModelFBSStocks::query()); FBSAmounts::query()->truncate();

        //////////////////
        /// GET STOCKS ///
        //////////////////

        $this->endpoint(Tokens::class, 'stocks')->init(function(Response $response, $attributes)
        {
            /** @var MarketplaceApiKey $token */ $token = $attributes['token'];

            try
            {
                foreach($response->json('result') as $stock) $this->results[ModelFBSStocks::class][$stock['warehouse_id']] = [
                    'id' => $stock['warehouse_id'],
                    'tid' => $token->id,
                    'last_request' => date('Y-m-d H:i:s'),
                    'active' => 'Y',
                    'name' => $stock['name'],

                    'is_rfbs' => $stock['is_rfbs'] ? 'Y' : null,
                    'is_able_to_set_price' => $stock['is_able_to_set_price'] ? 'Y' : null,
                    'has_entrusted_acceptance' => $stock['has_entrusted_acceptance'] ? 'Y' : null,

                    'dropoff_point_id' => strlen($v = $stock['first_mile_type']['dropoff_point_id']) ? $v : null,
                    'dropoff_timeslot_id' => $stock['first_mile_type']['dropoff_timeslot_id'] ?: null,
                    'first_mile_is_changing' => $stock['first_mile_type']['first_mile_is_changing'] ? 'Y' : null,
                    'first_mile_type' => strlen($v = $stock['first_mile_type']['first_mile_type']) ? $v : null,

                    'is_kgt' => $stock['is_kgt'] ? 'Y' : null,
                    'can_print_act_in_advance' => $stock['can_print_act_in_advance'] ? 'Y' : null,
                    'min_working_days' => $stock['min_working_days'],
                    'is_karantin' => $stock['is_karantin'] ? 'Y' : null,
                    'has_postings_limit' => $stock['has_postings_limit'] ? 'Y' : null,
                    'postings_limit' => $stock['postings_limit'],
                    'working_days' => count($stock['working_days']) ? implode(',', $stock['working_days']) : null,
                    'min_postings_limit' => $stock['min_postings_limit'],
                    'is_timetable_editable' => $stock['is_timetable_editable'] ? 'Y' : null,
                    'status' => $stock['status'],
                    'is_economy' => $stock['is_economy'] ? 'Y' : null,
                    'is_presorted' => $stock['is_presorted'] ? 'Y' : null,

                    'total' => 0
                ];
            }
            catch (\Throwable $e)
            {
                Log::channel('error')->error(['OZON FBS Stocks', $response->body(), (string) $e]); throw $this->isAccess($token) ? $e : new ErrorException($response);
            }
        });

        ///////////////////
        /// GET AMOUNTS ///
        ///////////////////

        while (true)
        {
            /** @var MarketplaceApiKey $token */ $stamp = floor(microtime(true) * 1000);

            foreach($manager->source->all('OZON') as $token)
            {
                if(($last_sku = Arr::get($this->last_skus, $token->id)) === false) continue;

                foreach(array_chunk(Prices::query()->where('token_id', $token->id)->where('sku', '>', $last_sku ?: 0)->orderBy('sku')->limit(2000)->pluck('sku')->all(), 500) as $chunk)
                {
                    $this->endpoint(Tokens::class, 'amounts', $chunk, $token); $this->last_skus[$token->id] = array_pop($chunk);
                }

                if($this->last_skus[$token->id] === $last_sku) $this->last_skus[$token->id] = false;
            }

            if(!$manager->count()) break;

            $manager->init(function(Response $response, array $attributes)
            {
                /** @var MarketplaceApiKey $token */ $token = $attributes['token'];

                try
                {
                    foreach($response->json('result') as $amount)
                    {
                        foreach(['present', 'reserved'] as $type)
                        {
                            if(!$amount[$type]) continue; $this->results[ModelFBSStocks::class][$amount['warehouse_id']]['total'] += $amount[$type];

                            $this->results[FBSAmounts::class][implode(' | ', [$amount['sku'], $amount['warehouse_id'], $type])] = [
                                'sku' => $amount['sku'],
                                'sid' => $amount['warehouse_id'],
                                'type' => $type,
                                'amount' => $amount[$type]
                            ];

                            //$this->history('history/ozon/'.$amount['sku'].'/fbs.csv', ' | '.implode(' : ', [$amount['warehouse_name'], $type, $amount[$type]]));
                        }
                    }
                }
                catch (\Throwable $e)
                {
                    Log::channel('error')->error(['OZON FBS Amounts', $response->body(), (string) $e]); throw $this->isAccess($token) ? $e : new ErrorException($response);
                }
            });

            if(floor(microtime(true) * 1000) - $stamp <= 500) sleep(1);
        }

        ModelFBSStocks::shortUpsert($this->results[ModelFBSStocks::class]); foreach(array_chunk($this->results[FBSAmounts::class], 2000) as $chunk) FBSAmounts::shortUpsert($chunk);

        Log::channel('ozon')->info(implode(' | ', ['RESULTS', Time::during(time() - $start)]));
        Log::channel('ozon')->info(implode(' | ', ['OZON FBS Stocks', ...Arr::map($this->counts(ModelFBSStocks::class), fn($v, $k) => $k.': '.$v)]));
        Log::channel('ozon')->info(implode(' | ', ['OZON FBS Amounts', 'FBSAmounts sku: '.FBSAmounts::query()->count(), 'FBSAmounts count: '.ModelFBSStocks::query()->sum('total')]));

        $this->operation->update(['next_start' => null, 'counter' => 0]);
    }
}
