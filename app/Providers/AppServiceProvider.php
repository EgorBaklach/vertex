<?php namespace App\Providers;

use App\Contexts\YMCProperties;
use App\Helpers\Func;
use App\Models\Dev\MarketplaceApiKey;
use App\Models\Management\Designs\Patterns;
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
        Proxies::class,
    ];

    public function register(): void
    {
        array_map(fn($class) => $this->app->extend($class, fn(SourceInterface $source) => new APIManager($source)), [Tokens::class, Proxies::class]);
    }

    public function boot(): void
    {
        $this->app->singleton(Patterns::class, fn() => Patterns::query()->get(['pattern', 'description'])->map(fn(Patterns $v) => ['key' => $v->pattern.' - '.$v->description, 'value' => substr($v->pattern, 1)])->toArray());

        $this->app->singleton('json_decoder', fn() => new ExtJsonDecoder(true, 512, JSON_BIGINT_AS_STRING));

        $this->app->singleton(ImageManagerInterface::class, fn() => new ImageManager(new Driver));

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

        $this->app->singleton('endpoints', fn(Application $app) => [
            Tokens::class => fn() => Func::call($app->make(Tokens::class), fn(APIManager $manager) => [
                APIManager::class => $manager,

                //////////
                /// WB ///
                //////////

                WB\Categories::class => [
                    'parent' => fn() => $manager->enqueue('https://content-api.wildberries.ru/content/v2/object/parent/all', 'WB'),
                    'children' => fn(int $cid) => $manager->enqueue('https://content-api.wildberries.ru/content/v2/object/all?'.http_build_query(['parentID' => $cid, 'limit' => 1000]), 'WB', 'get', null, $cid)
                ],
                WB\Properties::class => fn(int $cid) => $manager
                    ->enqueue('https://content-api.wildberries.ru/content/v2/object/charcs/'.$cid, 'WB', 'get', null, 'properties', $cid)
                    ->enqueue('https://content-api.wildberries.ru/content/v2/directory/tnved?subjectID='.$cid, 'WB', 'get', null, 'tnved', $cid),
                WB\Directories::class => fn(string $operation, int $pid) => $manager->enqueue('https://content-api.wildberries.ru/content/v2/directory/'.$operation.'?locale=ru', 'WB', 'get', null, $operation, $pid),
                WB\Products::class => [
                    ['card', 'https://content-api.wildberries.ru/content/v2/get/cards/list', ['settings' => ['cursor' => [], 'filter' => ['withPhoto' => -1]]]],
                    ['trash', 'https://content-api.wildberries.ru/content/v2/get/cards/trash', ['settings' => ['cursor' => [], 'filter' => ['withPhoto' => -1]]]]
                ],
                WB\Prices::class => fn(string $query, MarketplaceApiKey $token) => $manager->enqueue('https://discounts-prices-api.wildberries.ru/api/v2/list/goods/filter?'.$query, $token),
                WB\FBOStocks::class => function(string $operation, callable $callback = null) use ($manager)
                {
                    /** @var MarketplaceApiKey $token */

                    foreach($manager->source->all('WB') as $token) match($operation)
                    {
                        'create' => $manager->enqueue('https://seller-analytics-api.wildberries.ru/api/v1/warehouse_remains?groupByBarcode=true', $token),
                        'ping' => $manager->enqueue('https://seller-analytics-api.wildberries.ru/api/v1/warehouse_remains/tasks/'.$callback($token->id).'/status', $token),
                        'get' => $manager->enqueue('https://seller-analytics-api.wildberries.ru/api/v1/warehouse_remains/tasks/'.$callback($token->id).'/download', $token)
                    };

                    return $manager;
                },
                WB\FBSStocks::class => [
                    'amounts' => fn(WBFBSStocks $stock, array $skus, int $last_id) => $manager->enqueue('https://marketplace-api.wildberries.ru/api/v3/stocks/'.$stock->id, 'WB:'.$stock->tid, 'post', compact('skus'), $stock, $last_id),
                    'stocks' => function(string $operation) use ($manager)
                    {
                        foreach($manager->source->all('WB') as $token) match($operation)
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

                OZON\Categories::class => fn() => $manager->enqueue('https://api-seller.ozon.ru/v1/description-category/tree', 'OZON', 'post'),
                OZON\Properties::class => fn(string $operation, CT $ct) => $manager->enqueue('https://api-seller.ozon.ru/v1/description-category/attribute', 'OZON:'.$operation, 'post', ['description_category_id' => $ct->cid, 'type_id' => $ct->tid], $ct->cid, $ct->tid),
                OZON\Dictionaries::class => function(string $operation, OZONProperties $property, int $last_value_id = 0) use ($manager)
                {
                    $post = ['attribute_id' => $property->id, 'limit' => 2000] + compact('last_value_id') + Func::call($property->ctps->first(), fn(CTP $ctp) => ['description_category_id' => $ctp->cid, 'type_id' => $ctp->tid]);

                    $manager->enqueue('https://api-seller.ozon.ru/v1/description-category/attribute/values', 'OZON:'.$operation, 'post', $post, $property);
                },
                OZON\Products::class => fn(string $operation, array $values) => match($operation)
                {
                    'products' => [
                        ['attributes', 'https://api-seller.ozon.ru/v4/product/info/attributes', $values + ['sort_dir' => 'ASC']],
                        ['list', 'https://api-seller.ozon.ru/v3/product/list', $values]
                    ],
                    default => call_user_func(fn($post) => $manager->enqueue('https://api-seller.ozon.ru/v1/description-category/attribute/values/search', 'OZON:'.$operation, 'post', $post), [
                        'description_category_id' => $values['cid'],
                        'attribute_id' => $values['pid'],
                        'type_id' => $values['tid'],
                        'value' => $values['value'],
                        'limit' => 1
                    ])
                },
                OZON\Prices::class => fn(array $product_id, mixed $token) => $manager->enqueue('https://api-seller.ozon.ru/v3/product/info/list', $token, 'post', compact('product_id')),
                OZON\FBOStocks::class => fn(MarketplaceApiKey $token, ?int $offset, ?int $limit) => $manager->enqueue('https://api-seller.ozon.ru/v1/analytics/manage/stocks', $token, 'post', ['filter' => new StdClass] + compact('limit', 'offset')),
                OZON\FBSStocks::class => [
                    'amounts' => fn(array $sku, mixed $token) => $manager->enqueue('https://api-seller.ozon.ru/v1/product/info/stocks-by-warehouse/fbs', $token, 'post', compact('sku')),
                    'stocks' => function() use ($manager)
                    {
                        foreach($manager->source->all('OZON') as $token) $manager->enqueue('https://api-seller.ozon.ru/v1/warehouse/list', $token, 'post'); return $manager;
                    }
                ],

                //////////
                /// YM ///
                //////////

                YM\Categories::class => fn() => $manager->enqueue('https://api.partner.market.yandex.ru/categories/tree', 'YM', 'post', ['language' => 'RU']),
                YM\Properties::class => function(array $sids) use ($manager)
                {
                    foreach($sids as $sid) $manager->enqueue('https://api.partner.market.yandex.ru/category/'.$sid.'/parameters', 'YM', 'post'); return $manager;
                },
                YM\Products::class => fn(string $operation, MarketplaceApiKey $token, string $query, callable $injector) => match($operation)
                {
                    'mappings' => fn(bool $archived) => $manager->enqueue('https://api.partner.market.yandex.ru/businesses/'.$token->params['business']['id'].'/offer-mappings?'.$query, $token, 'post', compact('archived'), 'mappings', $injector),
                    'cards' => fn() => $manager->enqueue('https://api.partner.market.yandex.ru/businesses/'.$token->params['business']['id'].'/offer-cards?'.$query, $token, 'post', null, 'cards', $injector)
                },
                YM\Stocks::class => [
                    'stocks' => function() use ($manager): APIManager
                    {
                        foreach($manager->source->all('YM') as $token) $manager->enqueue('https://api.partner.market.yandex.ru/businesses/'.$token->params['business']['id'].'/warehouses', $token, 'get', null, FBSStocks::class);

                        $manager->enqueue('https://api.partner.market.yandex.ru/warehouses', 'YM', 'get', null, FBYStocks::class); return $manager;
                    },
                    'amounts' => fn(MarketplaceApiKey $token, string $query, bool $archived) => $manager->enqueue('https://api.partner.market.yandex.ru/campaigns/'.$token->params['id'].'/offers/stocks?'.$query, $token, 'post', ['withTurnover' => true] + compact('archived'), $archived)
                ]
            ]),
            Proxies::class => fn() => Func::call($app->make(Proxies::class), fn(APIManager $manager) => [
                APIManager::class => $manager,
                WB\WBPrices::class => fn(string $ids, int $key) => $manager->enqueue('https://card.wb.ru/cards/v3/detail?dest=-1275608&nm='.$ids, null, 'get', null, (string) Str::uuid(), $key)
            ])
        ]);
    }
}
