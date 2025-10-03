<?php namespace App\Providers;

use App\Contexts\YMCProperties;
use App\Helpers\Func;
use App\Models\Dev\MarketplaceApiKey;
use App\Models\Management\Designs\Patterns;
use ArrayObject;
use App\Models\Dev\OZON\{Categories, CT, CTP, Properties as OZONProperties, Types};
use App\Models\Dev\WB\{FBSStocks as WBFBSStocks, Categories as WBCategories};
use App\Models\Dev\YM\{Categories as YMCategories, FBSStocks, FBYStocks};
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageManagerInterface;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use App\Services\{APIManager, Sources\Proxies, Sources\SourceInterface, Sources\Tokens, OZON, YM, WB};
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use stdClass;

class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        YMCProperties::class,
        Tokens::class,
        Proxies::class
    ];

    public function register(): void
    {
        foreach([Tokens::class, Proxies::class] as $class) $this->app->extend($class, fn(SourceInterface $source) => new APIManager($source));
    }

    public function boot(): void
    {
        $this->app->singleton(Patterns::class, fn() => Patterns::query()->get(['pattern', 'description'])->map(fn(Patterns $v) => ['key' => $v->pattern.' - '.$v->description, 'value' => substr($v->pattern, 1)])->toArray());

        $this->app->singleton('json_decoder', fn() => new ExtJsonDecoder(true, 512, JSON_BIGINT_AS_STRING));

        $this->app->singleton(ImageManagerInterface::class, fn() => new ImageManager(new Driver));

        $this->app->singleton('timestamp', fn() => floor(microtime(true) * 1000));

        $this->app->singleton('categories', fn() => [
            'wb' => [
                'query' => fn(Builder $query): Builder => $query->has('parent')->where('childs', 0),
                'option' => fn(WBCategories $category) => $category->parent->name.' - '.$category->name,
                'field' => 'wb_cid'
            ],
            'ozon_type' => [
                'query' => fn(Builder $query): Builder => $query->has('categories')->whereNull('disabled'),
                'option' => fn(Types $type) => $type->categories->first()['name'].' - '.$type->name,
                'field' => 'ozon_tid'
            ],
            'ozon_category' => [
                'query' => fn(Builder $query, Get $get): Builder => $query->whereHas('types', fn(Builder $query) => $query->where('active', 'Y')->whereNull('disabled')->where('id', $get('ozon_tid')))->whereNull('disabled'),
                'option' => fn(Categories $category) => $category->name,
                'extender' => fn(Select $select) => $select->visible(fn(Get $get) => Func::call($get('ozon_tid'), fn($v) => strlen($v) && CT::where('tid', $v)->whereHas('category', fn(Builder $query) => $query->where('active', 'Y')->whereNull('disabled'))->count() > 1))->preload(),
                'field' => 'ozon_cid'
            ],
            'ym' => [
                'query' => fn(Builder $query): Builder => $query->has('parent')->where('childs', 0),
                'option' => fn(YMCategories $category) => $category->parent->name.' - '.$category->name,
                'field' => 'ym_cid'
            ]
        ]);

        $this->app->singleton('endpoints', fn() => new class(...func_get_args()) extends ArrayObject
        {
            public function __construct(private readonly Application $app, array $parameters)
            {
                foreach($parameters as $field => $value) $app->when(Tokens::class)->needs('$'.$field)->give($value);

                parent::__construct([
                    Tokens::class => fn() => Func::call($this->app->make(Tokens::class), fn(APIManager $manager) => [
                        APIManager::class => $manager,

                        //////////
                        /// WB ///
                        //////////

                        WB\Categories::class => [
                            'parent' => fn() => $manager->enqueue('https://content-api.wildberries.ru/content/v2/object/parent/all'),
                            'children' => fn(int $cid) => $manager->enqueue('https://content-api.wildberries.ru/content/v2/object/all?'.http_build_query(['parentID' => $cid, 'limit' => 1000]), null, 'get', null, $cid)
                        ],
                        WB\Properties::class => fn(string $position, string $operation, int $cid) => call_user_func(fn(string $endpoint) => $manager->enqueue($endpoint, $position, 'get', null, $operation, $cid), match($operation)
                        {
                            'properties' => 'https://content-api.wildberries.ru/content/v2/object/charcs/'.$cid,
                            'tnved' => 'https://content-api.wildberries.ru/content/v2/directory/tnved?subjectID='.$cid
                        }),
                        WB\Directories::class => fn(string $operation, int $pid) => $manager->enqueue('https://content-api.wildberries.ru/content/v2/directory/'.$operation.'?locale=ru', null, 'get', null, $operation, $pid),
                        WB\Products::class => [
                            ['card', 'https://content-api.wildberries.ru/content/v2/get/cards/list', ['settings' => ['cursor' => [], 'filter' => ['withPhoto' => -1]]]],
                            ['trash', 'https://content-api.wildberries.ru/content/v2/get/cards/trash', ['settings' => ['cursor' => [], 'filter' => ['withPhoto' => -1]]]]
                        ],
                        WB\Prices::class => fn(string $query, MarketplaceApiKey $token) => $manager->enqueue('https://discounts-prices-api.wildberries.ru/api/v2/list/goods/filter?'.$query, $token),
                        WB\FBOStocks::class => function(string $operation, callable $callback = null) use ($manager)
                        {
                            foreach($manager->source->all() as $token) match($operation)
                            {
                                'create' => $manager->enqueue('https://seller-analytics-api.wildberries.ru/api/v1/warehouse_remains?groupByBarcode=true', $token),
                                'ping' => $manager->enqueue('https://seller-analytics-api.wildberries.ru/api/v1/warehouse_remains/tasks/'.$callback($token).'/status', $token),
                                'get' => $manager->enqueue('https://seller-analytics-api.wildberries.ru/api/v1/warehouse_remains/tasks/'.$callback($token).'/download', $token)
                            };

                            return $manager;
                        },
                        WB\FBSStocks::class => [
                            'amounts' => fn(WBFBSStocks $stock, array $skus) => count($skus) && $manager->enqueue('https://marketplace-api.wildberries.ru/api/v3/stocks/'.$stock->id, $stock->token, 'post', compact('skus'), $stock),
                            'stocks' => function(string $operation) use ($manager)
                            {
                                foreach($manager->source->all() as $token) match($operation)
                                {
                                    'offices' => $manager->enqueue('https://marketplace-api.wildberries.ru/api/v3/offices', $token),
                                    'stocks' => $manager->enqueue('https://marketplace-api.wildberries.ru/api/v3/warehouses', $token)
                                };

                                return $manager;
                            }
                        ],

                        ////////////
                        /// OZON ///
                        ////////////

                        OZON\Categories::class => fn() => $manager->enqueue('https://api-seller.ozon.ru/v1/description-category/tree', null, 'post'),
                        OZON\Properties::class => fn(string $position, CT $ct) => $manager->enqueue('https://api-seller.ozon.ru/v1/description-category/attribute', $position, 'post', ['description_category_id' => $ct->cid, 'type_id' => $ct->tid], $ct->cid, $ct->tid),
                        OZON\Dictionaries::class => function(string $position, OZONProperties $property, int $last_value_id = 0) use ($manager)
                        {
                            $post = ['attribute_id' => $property->id, 'limit' => 2000] + compact('last_value_id') + Func::call($property->ctps->first(), fn(CTP $ctp) => ['description_category_id' => $ctp->cid, 'type_id' => $ctp->tid]);

                            $manager->enqueue('https://api-seller.ozon.ru/v1/description-category/attribute/values', $position, 'post', $post, $property);
                        },
                        OZON\Products::class => [
                            ['attributes', 'https://api-seller.ozon.ru/v4/product/info/attributes', ['sort_dir' => 'ASC']],
                            ['list', 'https://api-seller.ozon.ru/v3/product/list', []]
                        ],
                        OZON\Prices::class => fn(array $chunk, MarketplaceApiKey $token) => $manager->enqueue('https://api-seller.ozon.ru/v3/product/info/list', $token, 'post', ['product_id' => $chunk]),
                        OZON\FBOStocks::class => fn(MarketplaceApiKey $token, ?int $offset, ?int $limit) => $manager->enqueue('https://api-seller.ozon.ru/v1/analytics/manage/stocks', $token, 'post', ['filter' => new StdClass] + compact('limit', 'offset')),
                        OZON\FBSStocks::class => [
                            'amounts' => fn(array $sku, mixed $token) => $manager->enqueue('https://api-seller.ozon.ru/v1/product/info/stocks-by-warehouse/fbs', $token, 'post', compact('sku')),
                            'stocks' => function() use ($manager)
                            {
                                foreach($manager->source->all() as $token) $manager->enqueue('https://api-seller.ozon.ru/v1/warehouse/list', $token, 'post'); return $manager;
                            }
                        ],

                        //////////
                        /// YM ///
                        //////////

                        YM\Categories::class => fn() => $manager->enqueue('https://api.partner.market.yandex.ru/categories/tree', null, 'post', ['language' => 'RU']),
                        YM\Properties::class => fn(string $position, int $sid) => $manager->enqueue('https://api.partner.market.yandex.ru/category/'.$sid.'/parameters', $position, 'post'),
                        YM\Products::class => fn(string $operation, MarketplaceApiKey $token, string $query, callable $injector) => match($operation)
                        {
                            'mappings' => fn(bool $archived) => $manager->enqueue('https://api.partner.market.yandex.ru/businesses/'.$token->params['business']['id'].'/offer-mappings?'.$query, $token, 'post', compact('archived'), 'mappings', $injector),
                            'cards' => fn() => $manager->enqueue('https://api.partner.market.yandex.ru/businesses/'.$token->params['business']['id'].'/offer-cards?'.$query, $token, 'post', null, 'cards', $injector)
                        },
                        YM\Stocks::class => [
                            'stocks' => function() use ($manager): APIManager
                            {
                                foreach($manager->source->all() as $token) $manager->enqueue('https://api.partner.market.yandex.ru/businesses/'.$token->params['business']['id'].'/warehouses', $token, 'get', null, FBSStocks::class);

                                $manager->enqueue('https://api.partner.market.yandex.ru/warehouses', null, 'get', null, FBYStocks::class); return $manager;
                            },
                            'amounts' => fn(MarketplaceApiKey $token, string $query, bool $archived) => $manager->enqueue('https://api.partner.market.yandex.ru/campaigns/'.$token->params['id'].'/offers/stocks?'.$query, $token, 'post', ['withTurnover' => true] + compact('archived'), $archived)
                        ]
                    ]),
                    Proxies::class => fn() => Func::call($this->app->make(Proxies::class), fn(APIManager $manager) => [
                        APIManager::class => $manager,
                        WB\WBPrices::class => fn(string $ids, int $key) => $manager->enqueue('https://card.wb.ru/cards/v4/detail?dest=-1275608&nm='.$ids, null, 'get', null, (string) Str::uuid(), $key)
                    ])
                ]);
            }
        });
    }
}
