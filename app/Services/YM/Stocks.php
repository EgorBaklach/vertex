<?php namespace App\Services\YM;

use App\Helpers\Time;
use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\Traits\CustomQueries;
use App\Services\APIManager;
use App\Services\Sources\Tokens;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Traits\{Queries, Repeater, Tracker};
use App\Models\Dev\YM\{FBSAmounts, FBSStocks, FBYAmounts, FBYStocks};
use App\Services\MSAbstract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use ReflectionClass;
use Throwable;

class Stocks extends MSAbstract
{
    use Queries, Tracker;

    /** @var string[]|bool[] */
    private array $cursors = [];

    /** @var string[] */
    private array $stocks = [];

    private const classes = [
        FBSStocks::class => 'update',
        FBYStocks::class => 'update',
        FBSAmounts::class => 'truncate',
        FBYAmounts::class => 'truncate'
    ];

    public function __get(string $name): callable
    {
        return fn(array $value): array|callable => match($name)
        {
            FBSStocks::class => fn(MarketplaceApiKey $token) => [
                'tid' => $token->id,
                'express' => $value['express'] ? 'Y' : null
            ],
            FBYStocks::class => fn() => [
                'building' => $value['address']['building'] ?? null,
                'block' => $value['address']['block'] ?? null
            ],
            FBYAmounts::class => [
                'turnoverType' => $value['turnoverSummary']['turnover'],
                'turnoverDays' => $value['turnoverSummary']['turnoverDays']
            ],
            default => []
        };
    }

    /**
     * Импорт товаров через равное кол-во пройденного времени, для сохрания ОЗУ
     *
     * @return void
     */
    public function import(): void
    {
        /** @var string|Model|CustomQueries $class */ foreach($this->results as $class => $values) foreach(array_chunk($values, 2000) as $chunk) $class::shortUpsert($chunk); $this->results = [];
    }

    private function encode(string $page_token = ''): string
    {
        return http_build_query(['limit' => 200] + compact('page_token'));
    }

    public function __invoke(): void
    {
        /** @var string|Model|CustomQueries $class */ $start = time(); $manager = $this->endpoint(Tokens::class, APIManager::class); $DB = DB::connection('dev');

        foreach(self::classes as $class => $method) match ($method)
        {
            'update' => $this->updateInstances(match($class){FBSStocks::class => $class::query()->whereIn('tid', array_keys($manager->source->all())), default => $class::query()}), 'truncate' => $class::query()->truncate()
        };

        $manager->source->throw = function(Throwable $e, $attributes, ...$data) use ($manager)
        {
            $manager->enqueue(...array_values($attributes), ...$data); $attributes['token']->inset('abort');
        };

        //////////////
        /// STOCKS ///
        //////////////

        $this->endpoint(Tokens::class, 'stocks')->init(function(Response $response, array $attributes, $class)
        {
            foreach($response->json('result.warehouses') as $warehouse)
            {
                $this->stocks[$warehouse['id']] = [
                    'name' => $warehouse['name'],
                    'class' => match ($class)
                    {
                        FBSStocks::class => FBSAmounts::class,
                        FBYStocks::class => FBYAmounts::class
                    }
                ];

                $this->results[$class][$warehouse['id']] = [
                    'id' => $warehouse['id'],
                    'last_request' => date('Y-m-d H:i:s'),
                    'active' => 'Y',
                    'name' => $warehouse['name'],
                    'city' => $warehouse['address']['city'],
                    'street' => $warehouse['address']['street'],
                    'number' => $warehouse['address']['number'],
                    'latitude' => $warehouse['address']['gps']['latitude'],
                    'longitude' => $warehouse['address']['gps']['longitude'],
                    ...call_user_func($this->{$class}, $warehouse)($attributes['token'])
                ];
            }
        });

        $this->import();

        ///////////////
        /// AMOUNTS ///
        ///////////////

        $DB->statement('SET FOREIGN_KEY_CHECKS=0;');

        while(true)
        {
            foreach($manager->source->all() as $token)
            {
                foreach([0, 1] as $archived)
                {
                    if($query = $this->cursors[$token->id][$archived] ?? $this->encode())
                    {
                        $this->endpoint(Tokens::class, 'amounts', $token, $query, !!$archived);
                    }
                }
            }

            if(!$manager->count()) break;

            $manager->init(function(Response $response, array $attributes, $archived)
            {
                $token = $attributes['token'];

                try
                {
                    ['paging' => $paging, 'warehouses' => $warehouses] = $response->json('result');

                    foreach($warehouses as $warehouse)
                    {
                        $class = $this->stocks[$warehouse['warehouseId']]['class'];

                        foreach($warehouse['offers'] as $offer)
                        {
                            //$amounts = [];

                            foreach($offer['stocks'] as $stock)
                            {
                                //$amounts[] = $stock['type'].' : '.$stock['count'];

                                $this->results[$class][implode('_', [$offer['offerId'], $warehouse['warehouseId'], $stock['type']])] = [
                                    'offer_ID' => $offer['offerId'],
                                    'sid' => $warehouse['warehouseId'],
                                    'updatedAt' => $offer['updatedAt'],
                                    'type' => $stock['type'],
                                    'count' => $stock['count'],
                                    ...call_user_func($this->{$class}, $offer)
                                ];
                            }

                            //if(count($amounts)) $this->history('history/ym/'.$offer['offerId'].'/'.$class::logHistoryName, ' | '.implode(' : ', [$this->stocks[$warehouse['warehouseId']]['name'], ...$amounts]));
                        }
                    }

                    $this->cursors[$token->id][$archived] = strlen($paging['nextPageToken'] ?? '') ? $this->encode($paging['nextPageToken']) : false;
                }
                catch (Throwable $e)
                {
                    Log::channel('error')->error(implode(' | ', ['YM Stock Amounts', $response->status(), $response->body(), (string) $e])); throw $e;
                }
            });

            if($this->due(600 * 1000)) $this->import();  // ЧЕРЕЗ КАЖДЫЕ 10 МИНУТ
        }

        $this->import(); $DB->statement('SET FOREIGN_KEY_CHECKS=1;');

        Log::channel('ym')->info(implode(' | ', ['RESULT', Time::during(time() - $start)])); foreach(self::classes as $class => $method) $this->log(new ReflectionClass($class), $method);

        $this->operation->update(['next_start' => $this->operation->start > strtotime('today 14:00') ? null : strtotime('today 19:00'), 'counter' => 0]);
    }
}
