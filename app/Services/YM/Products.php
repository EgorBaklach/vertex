<?php namespace App\Services\YM;

use App\Exceptions\Http\ErrorException;
use App\Helpers\{Arr, Func, Time};
use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\Schedule;
use App\Models\Dev\Traits\CustomQueries;
use App\Models\Dev\YM\{
    Categories,
    CommodityCodes,
    Docs,
    Notices,
    PCC,
    PPV,
    Prices,
    Products as ModelProducts,
    Rating,
    Recommendations,
    SellingPrograms,
    Times
};
use App\Services\APIManager;
use App\Services\MSAbstract;
use App\Services\Sources\Tokens;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Storage;
use App\Services\Traits\{Queries, Repeater, Tracker};
use App\Services\YM\Traits\RecommendationFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use Throwable;

class Products extends MSAbstract
{
    use Queries, Repeater, RecommendationFields, Tracker;

    /** @var string[]|bool[] */
    private array $cursors = [];

    private array $fields = [
        'sku_id' => false,
        'last_request' => false,
        'active' => true,
        'archived' => true,
        'tid' => false,
        'cid' => false,
        'modelId' => false,
        'offerId' => false,
        'modelName' => false,
        'skuName' => false,
        'name' => true,
        'vendor' => true,
        'vendorCode' => true,
        'barcodes' => true,
        'description' => true,
        'manufacturerCountries' => true,
        'weightDimensions' => true,
        'tags' => true,
        'boxCount' => true,
        'cardStatus' => false,
        'type' => true,
        'downloadable' => true,
        'adult' => true
    ];

    private const classes = [
        ModelProducts::class => ['method' => 'update', 'foreign_key_check' => false],
        CommodityCodes::class => ['method' => 'update', 'foreign_key_check' => true],

        PCC::class => ['method' => 'truncate', 'foreign_key_check' => true],
        Prices::class => ['method' => 'truncate', 'foreign_key_check' => true],
        SellingPrograms::class => ['method' => 'truncate', 'foreign_key_check' => true],
        Times::class => ['method' => 'truncate', 'foreign_key_check' => true],
        Docs::class => ['method' => 'truncate', 'foreign_key_check' => true],

        PPV::class => ['method' => 'truncate', 'foreign_key_check' => false],
        Notices::class => ['method' => 'truncate', 'foreign_key_check' => true],
        Rating::class => ['method' => 'truncate', 'foreign_key_check' => true],

        Recommendations::class => ['method' => 'skip', 'foreign_key_check' => true]
    ];

    protected const limit = 10;

    /**
     * Импорт товаров через равное кол-во пройденного времени, для сохрания ОЗУ
     *
     * @param Connection $DB
     * @return void
     */
    private function import(Connection $DB): void
    {
        /** @var Model $class */ if(count($this->results[Categories::class] ?? [])) Categories::upsert($this->results[Categories::class], []);

        foreach(self::classes as $class => $value)
        {
            if(!$value['foreign_key_check']) $DB->statement('SET FOREIGN_KEY_CHECKS=0;');

            foreach(array_chunk($this->results[$class] ?? [], 2000) as $chunk) $class::query()->upsert($chunk, [], $class === ModelProducts::class ? $this->fields : null);

            if(!$value['foreign_key_check']) $DB->statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->results = [];
    }

    private function mappings(array $result, MarketplaceApiKey $token): callable
    {
        ['paging' => $paging, 'offerMappings' => $products] = $result;

        return function(array $post) use ($paging, $products, $token): bool|string
        {
            foreach($products as $product)
            {
                // PRODUCTS

                $this->results[ModelProducts::class][$product['offer']['offerId']] = array_combine(array_keys($this->fields), [
                    $product['mapping']['marketSku'] ?? null,
                    date('Y-m-d H:i:s'),
                    'Y',
                    ($product['offer']['archived'] ?? $post['archived']) ? 'Y' : null,
                    $token->id,
                    $cid = $product['mapping']['marketCategoryId'],
                    $product['mapping']['marketModelId'] ?? null,
                    $product['offer']['offerId'],
                    strlen($product['mapping']['marketModelName'] ?? '') ? $product['mapping']['marketModelName'] : null,
                    strlen($product['mapping']['marketSkuName'] ?? '') ? $product['mapping']['marketSkuName'] : null,
                    $product['offer']['name'],
                    strlen($product['offer']['vendor'] ?? '') ? $product['offer']['vendor'] : null,
                    strlen($product['offer']['vendorCode'] ?? '') ? $product['offer']['vendorCode'] : null,
                    count($barcodes = $product['offer']['barcodes'] ?? []) ? implode(',', $barcodes) : null,
                    strlen($product['offer']['description'] ?? '') ? $product['offer']['description'] : null,
                    call_user_func(fn(...$countries) => count($countries) ? implode(',', $countries) : null, ...($product['offer']['manufacturerCountries'] ?? [])),
                    call_user_func(fn(...$dimensions) => count($dimensions) ? implode(',', $dimensions) : null, ...Arr::map(['length', 'width', 'height', 'weight'], fn($v) => $product['offer']['weightDimensions'][$v] ?? '')),
                    !array_key_exists('tags', $product['offer']) ? null : implode(',', $product['offer']['tags']),
                    $product['offer']['boxCount'] ?? null,
                    $product['offer']['cardStatus'],
                    $product['offer']['type'] ?? null,
                    ($product['offer']['downloadable'] ?? false) ? 'Y' : null,
                    ($product['offer']['adult'] ?? false) ? 'Y' : null,
                ]);

                // CATEGORIES

                $this->results[Categories::class][$cid] ??= ['id' => $cid, 'cnt' => 0]; $this->results[Categories::class][$cid]['cnt']++;

                // DOCS

                foreach(['pictures' => 'picture', 'videos' => 'video', 'manuals' => 'manual'] as $key => $type)
                {
                    foreach($product['offer']['mediaFiles'][$key] ?? [] as $doc)
                    {
                        $this->results[Docs::class][implode('_', [$product['offer']['offerId'], md5($doc['url'])])] = [
                            'offer_id' => $product['offer']['offerId'],
                            'last_request' => date('Y-m-d H:i:s'),
                            'type' => $type,
                            'state' => $doc['uploadState'],
                            'value' => $doc['url'],
                            'title' => strlen($doc['title'] ?? '') ? $doc['title'] : null
                        ];
                    }
                }

                foreach($product['offer']['certificates'] ?? [] as $certificate)
                {
                    $this->results[Docs::class][implode('_', [$product['offer']['offerId'], md5($certificate)])] = [
                        'offer_id' => $product['offer']['offerId'],
                        'last_request' => date('Y-m-d H:i:s'),
                        'type' => 'certificate',
                        'state' => 'UPLOADED',
                        'value' => $certificate,
                        'title' => null
                    ];
                }

                // TIMES

                foreach(['shelfLife' => 'shelf', 'lifeTime' => 'life', 'guaranteePeriod' => 'guarantee'] as $key => $type)
                {
                    if(!array_key_exists($key, $product['offer'])) continue;

                    $this->results[Times::class][implode('_', [$product['offer']['offerId'], $type])] = [
                        'offer_id' => $product['offer']['offerId'],
                        'type' => $type,
                        'unit' => $product['offer'][$key]['timeUnit'],
                        'period' => $product['offer'][$key]['timePeriod'],
                        'comment' => strlen($product['offer'][$key]['comment'] ?? '') ? $product['offer'][$key]['comment'] : null
                    ];
                }

                // COMMODITY CODES

                foreach($product['offer']['commodityCodes'] ?? [] as $commodityCode)
                {
                    $this->results[CommodityCodes::class][$commodityCode['code']] ??= [
                        'code' => $commodityCode['code'],
                        'last_request' => date('Y-m-d H:i:s'),
                        'active' => 'Y',
                        'type' => $commodityCode['type']
                    ];

                    $this->results[PCC::class][implode('_', [$product['offer']['offerId'], $commodityCode['code']])] = [
                        'offer_id' => $product['offer']['offerId'],
                        'commodity_code' => $commodityCode['code']
                    ];
                }

                // SELLING PROGRAMS

                foreach($product['offer']['sellingPrograms'] ?? [] as $program)
                {
                    $this->results[SellingPrograms::class][implode('_', [$product['offer']['offerId'], $program['sellingProgram']])] = [
                        'offer_id' => $product['offer']['offerId'],
                        'program' => $program['sellingProgram'],
                        'status' => $program['status']
                    ];
                }

                // PRICES

                $pricies = [];

                foreach(['basic', 'purchase', 'additionalExpenses' => 'expenses', 'cofinance'] as $key => $type)
                {
                    if(!array_key_exists($key = is_string($key) ? $key : $type.'Price', $product['offer'])) continue;

                    $pricies[] = $type.': '.Func::call($product['offer'][$key], fn(array $price) => match ($type)
                        {
                            'basic' => implode(' / ', Arr::map(['value', 'discountBase'], fn($k) => $price[$k] ?? 0)), default => $price['value']
                        });

                    $this->results[Prices::class][implode('_', [$product['offer']['offerId'], $type])] = [
                        'offer_id' => $product['offer']['offerId'],
                        'type' => $type,
                        'updatedAt' => $product['offer'][$key]['updatedAt'],
                        'value' => $product['offer'][$key]['value'],
                        'discountBase' => $product['offer'][$key]['discountBase'] ?? null
                    ];
                }

                if(count($pricies)) Storage::disk('local')->append('history/ym/'.$product['offer']['offerId'].'/price.csv', implode(' | ', [date('Y-m-d H:i:s'), ...$pricies]));
            }

            return strlen($paging['nextPageToken'] ?? '') ? $this->encode($paging['nextPageToken']) : false;
        };
    }

    private function cards(array $result, MarketplaceApiKey $token): callable
    {
        ['paging' => $paging, 'offerCards' => $cards] = $result;

        return function() use ($paging, $cards, $token): bool|string
        {
            foreach($cards as $card)
            {
                // PRODUCTS IF NOT EXIST

                $this->results[ModelProducts::class][$card['offerId']] ??= array_combine(array_keys($this->fields), [
                    $card['mapping']['marketSku'] ?? null,
                    date('Y-m-d H:i:s'),
                    null,
                    null,
                    $token->id,
                    $card['mapping']['marketCategoryId'],
                    $card['mapping']['marketModelId'] ?? null,
                    $card['offerId'],
                    strlen($card['mapping']['marketModelName'] ?? '') ? $card['mapping']['marketModelName'] : null,
                    strlen($card['mapping']['marketSkuName'] ?? '') ? $card['mapping']['marketSkuName'] : null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    $card['cardStatus'],
                    null,
                    null,
                    null,
                ]);

                // PROPERTIES

                foreach($card['parameterValues'] ?? [] as $prop)
                {
                    $this->results[PPV::class][implode('_', [$card['offerId'], $prop['parameterId'], $prop['valueId'] ?? 0, $prop['value'] ?? ''])] = [
                        'offer_id' => $card['offerId'],
                        'property_id' => $prop['parameterId'],
                        'pvid' => $prop['valueId'] ?? 0,
                        'value' => $prop['value'] ?? '',
                        'uid' => $prop['unitId'] ?? null
                    ];
                }

                // RATING

                if(count(array_filter($rating = ['status' => $card['contentRatingStatus'] ?? null, 'rating' => $card['contentRating'] ?? null, 'average' => $card['averageContentRating'] ?? null])))
                {
                    $this->results[Rating::class][$card['offerId']] = ['offer_id' => $card['offerId'], 'last_request' => date('Y-m-d H:i:s')] + $rating;
                }

                // RECOMMENDATIONS

                if(count($card['recommendations'] ?? []))
                {
                    $this->results[Recommendations::class][$card['offerId']] = ['product_offer_id' => $card['offerId']];

                    foreach(array_keys(self::recFields) as $field) $this->results[Recommendations::class][$card['offerId']][$field] = null;

                    foreach($card['recommendations'] as $r) $this->results[Recommendations::class][$card['offerId']][$r['type']] = ($r['percent'] ?? 0) << 7 | ($r['remainingRatingPoints'] ?? 0);
                }

                // NOTICES

                foreach(['errors' => 'error', 'warnings' => 'warning'] as $m => $o)
                {
                    foreach($card[$m] ?? [] as $value) $this->results[Notices::class][] = [
                        'offer_id' => $card['offerId'],
                        'type' => $o,
                        'message' => $value['message'],
                        'comment' => $value['comment']
                    ];
                }
            }

            return strlen($paging['nextPageToken'] ?? '') ? $this->encode($paging['nextPageToken']) : false;
        };
    }

    private function encode(string $page_token = ''): string
    {
        return http_build_query(['limit' => 200] + compact('page_token'));
    }

    public function __invoke(): void
    {
        /** @var string|Model|CustomQueries $class */ $start = time(); $day = date('N') * 1; $manager = $this->endpoint(Tokens::class, APIManager::class); $DB = DB::connection('dev');

        $this->fields = Arr::map($this->fields, fn($v, $k) => DB::raw($v ? 'CASE WHEN `active`=\'Y\' THEN `'.$k.'` ELSE VALUES(`'.$k.'`) END' : 'VALUES(`'.$k.'`)'));

        $manager->source->ttl = [
            'connectTimeout' => 60,
            'timeout' => 45
        ];

        if($this->operation->counter === 1)
        {
            foreach(self::classes as $class => $value) match ($value['method'])
            {
                'update' => $this->updateInstances($class::query()), 'truncate' => $class::query()->truncate(), default => null
            };

            Recommendations::query()->where('product_offer_id', '!=', '0')->delete(); Categories::query()->update(['cnt' => 0]);
        }

        while(true)
        {
            foreach($manager->source->all('YM') as $token)
            {
                if(!in_array($day, $token->days)) continue;

                foreach([0, 1] as $archived)
                {
                    if($query = $this->cursors[$token->id]['mappings'][$archived] ?? $this->encode())
                    {
                        $this->endpoint(Tokens::class, 'mappings', $token, $query, fn(bool|string $query) => $this->cursors[$token->id]['mappings'][$archived] = $query, !!$archived);
                    }
                }

                if($query = $this->cursors[$token->id]['cards'] ?? $this->encode())
                {
                    $this->endpoint(Tokens::class, 'cards', $token, $query, fn(bool|string $query) => $this->cursors[$token->id]['cards'] = $query);
                }
            }

            if(!$manager->count()) break;

            $manager->init(function(Response $response, array $attributes, string $operation, callable $injector)
            {
                try
                {
                    $injector($this->{$operation}($response->json('result'), $attributes['token'])($attributes['post']));
                }
                catch (Throwable $e)
                {
                    Log::channel('error')->error(implode(' | ', ['YM Products', $response->status(), $response->body(), (string) $e]));

                    throw ($this->isAccess($attributes['token']) || $injector(false)) ? $e : new ErrorException($response);
                }
            });

            if($this->due(300 * 1000)) $this->import($DB);
        }

        if(count($this->results)) $this->import($DB);

        Schedule::shortUpsert([
            ['market' => 'YM', 'operation' => 'PRODUCTS', 'next_start' => strtotime('tomorrow 9:00'), 'counter' => 0],
            ['market' => 'YM', 'operation' => 'STOCKS', 'next_start' => time(), 'counter' => 0]
        ]);

        Log::channel('ym')->info(implode(' | ', ['RESULT', Time::during(time() - $start)])); foreach(self::classes as $class => $value) $this->log(new ReflectionClass($class), $value['method']);
    }
}
