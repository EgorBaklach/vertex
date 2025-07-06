<?php namespace App\Filament\Resources\Management;

use App\Filament\Clusters\Management;
use App\Filament\Forms\Components\Autocomplete;
use App\Helpers\Func;
use App\Models\Dev\OZON\Properties;
use App\Models\Dev\OZON\PV;
use Closure;
use App\Filament\Resources\Management\Pages\{CreateProducts, EditProducts, ListProducts};
use App\Models\Dev\OZON\CT;
use App\Models\Management\{Categories, Designs\Patterns, Products};
use Filament\Forms\Components\{Checkbox, Fieldset, Select, TextInput};
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class ProductsResource extends Resource
{
    protected static ?string $model = Products::class;

    protected static ?string $cluster = Management::class;

    protected static ?string $modelLabel = 'Базовый товар';
    protected static ?string $pluralModelLabel = 'Базовые товары';

    protected static ?string $navigationLabel = 'Базовые товары';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Management';

    private static Categories|false|null $category = null;

    private const fields = [
        'units' => [
            'unit_dimension' => ['Единица измерения габаритов', ['sm' => 'сантиметры', 'mm' => 'миллиметры', 'dm' => 'дюймы'], [
                'default' => 6,
                'sm' => 5,
                'md' => 4,
            ]],
            'unit_weight' => ['Единица измерения веса', ['g' => 'граммы', 'kg' => 'килограммы', 'ft' => 'фунты'], [
                'default' => 6,
                'sm' => 5,
                'md' => 4,
            ]],
            'nds' => ['НДС', [0 => '0%', 5 => '5%', 7 => '7%', 10 => '10%', 12 => '12%', 13 => '13%', 20 => '20%'], [
                'default' => 12,
                'sm' => 2,
                'md' => 4,
            ]]
        ],
        'dimensions' => [
            'length' => ['Длина упаковки', 'unit_dimension'],
            'height' => ['Высота упаковки', 'unit_dimension'],
            'width' => ['Ширина упаковки', 'unit_dimension'],
            'weight' => ['Вес товара в упаковке', 'unit_weight'],
        ]
    ];

    private const grid = [
        2 => [
            'default' => 3,
            'md' => 2,
            'xl' => 1
        ],
        10 => [
            'default' => 9,
            'md' => 10,
            'xl' => 11
        ],
        6 => [
            'default' => 12,
            'sm' => 6
        ],
        3 => [
            'default' => 6,
            'md' => 3
        ]
    ];

    private static function expand(Categories $category): string
    {
        return implode(' / ', Arr::map(array_keys(App::make('categories')), fn($v) => $v . ' - <b>' . $category->{$v}->name . '</b>'));
    }

    private static function category(?int $cid): Categories|false
    {
        return self::$category ?? self::$category = $cid ? Categories::find($cid) : false;
    }

    private static function integrate($node, $parameters)
    {
        foreach($parameters as $params) $node->{array_shift($params)}(...$params); return $node;
    }

    private static function ozon(Get $get, Collection $collection): array
    {
        /**
         * @var Properties[] $collection
         * @var Checkbox|TextInput $input
         */

        $properties = [
            'inputs' => [],
            'complex' => [
                'props' => [],
                'relations' => []
            ]
        ];

        foreach($collection as $property)
        {
            $input = call_user_func(fn($class, ...$parameters) => self::integrate($class::make('property_ozon_'.$property->id), $parameters), ...match($property->type)
            {
                'Decimal', 'Integer' => [TextInput::class, [['Decimal' => 'numeric', 'Integer' => 'integer'][$property->type]], ['minValue', 0], ['suffixIcon', 'heroicon-m-chevron-up-down']],
                'URL' => [TextInput::class, ['url'], ['suffixIcon', 'heroicon-m-link']],
                'Boolean' => [Checkbox::class],
                'String' => match(!!$property->did)
                {
                    //->getSearchResultsUsing(fn (string $search): array => User::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                    //true => [Select::class, ['model', $property], ['getOptionLabelFromRecordUsing', fn(PV $pv) => $pv->value], ['relationship', 'pvs'], ['searchable'], ['reactive'], ['preload']],
                    true => [Select::class, ['searchable'], ['reactive'], ['preload'], ['getSearchResultsUsing', fn(string $search) => PV::query()
                        ->where('value', 'REGEXP', "(?i)^$search")
                        ->where('did', $property->did)
                        ->where('active', 'Y')
                        ->limit(50)
                        ->pluck('value', 'id')
                        ->toArray()
                    ]],
                    false => [TextInput::class]
                }
            });

            if(strlen($property->description)) $input->helperText(new HtmlString($property->description)); if($property->is_required === 'Y') $input->required();

            if($property->attribute_complex_id)
            {
                $properties['complex']['props'][$property->attribute_complex_id][] = 'property_ozon_'.$property->id;
                $properties['complex']['relations'][$property->id] = $property->attribute_complex_id;
            }

            $properties['inputs'][$property->id] = $input->label($property->name);
        }

        foreach($properties['complex']['relations'] as $cpid => $cid)
        {
            if(count($rules = array_filter($properties['complex']['props'][$cid], fn($pid) => $pid !== 'property_ozon_'.$cpid)))
            {
                $properties['inputs'][$cpid]->requiredWith(implode(',', $rules));
            }
        }

        return $properties['inputs'];
    }

    /*private static function wb(Categories $category): array
    {
        return [
            TextInput::make('wb_1')->required(),
            TextInput::make('wb_2')->required()
        ];
    }

    private static function ym(Categories $category): array
    {
        return [
            TextInput::make('ym_1')->required(),
            TextInput::make('ym_2')->required()
        ];
    }*/

    public static function form(Form $form): Form
    {
        return $form
            ->columns([
                'default' => 12,
                'sm' => 12,
                'md' => 12,
                'lg' => 12,
                'xl' => 12,
                '2xl' => 12,
            ])
            ->schema([
                Select::make('active')->options(['Y' => 'Y'])->placeholder('N')->columnSpan(self::grid[2])->default('Y')->label('Вкл:'),
                Autocomplete::make('name')->setOptions(App::make(Patterns::class))->columnSpan(self::grid[10])->required()->label('Наименование:'),
                Select::make('cid')
                    ->getOptionLabelFromRecordUsing(fn(Categories $category) => self::expand($category))
                    ->relationship('category')
                    ->columnSpanFull()
                    ->searchable()
                    ->allowHtml()
                    ->required()
                    ->reactive()
                    ->preload()
                    ->createOptionForm(fn(Form $form) => $form
                        ->columns(6)
                        ->schema([
                            ...Arr::map(App::make('categories'), fn($v, $k) => call_user_func($v['extender'] ?? fn($select) => $select, Select::make($v['field'])
                                ->getOptionLabelFromRecordUsing(fn(Model $model) => call_user_func($v['option'], $model))
                                ->relationship($k, 'name', fn(Builder $query, Get $get) => call_user_func($v['query'], $query->where('active', 'Y'), $get))
                                ->label(ucfirst($k) . ':')
                                ->optionsLimit(50)
                                ->columnSpanFull()
                                ->searchable()
                                ->required()
                                ->reactive())
                            )
                        ])
                    )
                    ->createOptionUsing(static function (Select $component, array $data, Form $form)
                    {
                        // SELECT count(`ozon_ct`.`cid`) as cnt, GROUP_CONCAT(`ozon_categories`.`name` SEPARATOR ' || '), `ozon_ct`.`tid` FROM `ozon_ct`
                        // JOIN `ozon_types` on `ozon_ct`.`tid`=`ozon_types`.`id`
                        // JOIN `ozon_categories` on `ozon_ct`.`cid`=`ozon_categories`.`id`
                        // WHERE `ozon_types`.`disabled` is null AND `ozon_categories`.`disabled` is null
                        // GROUP BY `ozon_ct`.`tid` ORDER BY cnt DESC;

                        // SELECT `ozon_ctp`.`cid`, `ozon_ctp`.`tid`, `ozon_ctp`.`pid`, `ozon_properties`.`name` FROM `ozon_ctp`
                        // JOIN `ozon_properties` ON `ozon_ctp`.`pid`=`ozon_properties`.`id`
                        // WHERE `ozon_ctp`.`cid`=200000933 AND `ozon_ctp`.`tid`=93244;

                        // В форме добавления "Категории базового товара", при выборе "Типа товара" ОЗОН без указания "Категории" - "Категория" выбирается из запроса.
                        if (!strval($data['ozon_cid'] ?? null)) $data['ozon_cid'] = CT::query()->where('tid', $data['ozon_tid'])->pluck('cid')->first();

                        $record = $component->getRelationship()->getRelated();
                        $record->fill($data + ['name' => implode(' / ', Arr::map($form->getComponents(), fn(Select $component) => $component->getOptionLabel()))]);
                        $record->save();

                        $form->model($record)->saveRelationships();

                        return $record->getKey();
                    })
                    ->label('Категория:'),

                Autocomplete::make('article')->setOptions(App::make(Patterns::class))->columnSpan(self::grid[6])->required()->label('Артикул:'),
                Autocomplete::make('barcode')->setOptions(App::make(Patterns::class))->columnSpan(self::grid[6])->label('Баркод:'),
                TextInput::make('discount_price')->columnSpan(self::grid[6])->numeric()->required()->label('Цена товара с учетом скидок:'),
                TextInput::make('price')->numeric()->columnSpan(self::grid[6])->label('Цена до скидок:'),

                ...Arr::map(self::fields['units'], fn($v, $k) => call_user_func(fn($name, $options, $cols) => Select::make($k)->options($options)->required()->columnSpan($cols)->label($name . ':'), ...$v)),
                ...Arr::map(self::fields['dimensions'], fn($v, $k) => call_user_func(fn($name) => TextInput::make($k)->numeric()->required()->columnSpan(self::grid[3])->label($name . ':'), ...$v)),

                ...Arr::map(['ozon', /*'wb', 'ym'*/], fn($v) => Fieldset::make($v)->visible(fn(Get $get) => $get('cid'))->schema(fn(Get $get) => !self::category($get('cid')) ? [] : self::{$v}($get, match($v)
                {
                    'ozon' => Properties::query()
                        ->whereHas('ctps', fn(Builder $query) => $query->where('cid', self::$category->ozon_cid)->where('tid', self::$category->ozon_tid))
                        ->where('active', 'Y')
                        ->orderByDesc('is_required')
                        ->orderBy('id')
                        ->get()
                })))
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([25, 50, 100, 250])
            ->recordClasses('tbl-rows-vertical-position')
            ->columns([
                TextColumn::make('id')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->label('ID'),
                TextColumn::make('cid')
                    ->getStateUsing(fn(Products $product) => self::expand($product->category))
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->html()
                    ->wrap()
                    ->label('Категория'),
                TextColumn::make('info')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->getStateUsing(fn(Products $product) => new HtmlString(implode('<br>', [
                        '**Наименование**: ' . $product->name,
                        '**Артикул**: ' . $product->article,
                        '**Баркод**: ' . ($product->barcode ?? '*Не установлен*'),
                        '---------------------',
                        '**Дата создания**: ' . $product->create_date,
                        '**Активность**: ' . ($product->active ?? '*N*')
                    ])))
                    ->markdown()
                    ->label('Информация о товаре'),
                TextColumn::make('price')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->getStateUsing(fn(Products $product) => new HtmlString(implode('<br>', [
                        '**Цена с учетом скидки**: ' . number_format($product->discount_price, 0, '.', '&nbsp;') . '&nbsp;₽',
                        '**Цена до скидок**: ' . ($product->price ? number_format($product->price, 0, '.', '&nbsp;') . '&nbsp;₽' : '*Не установлена*'),
                        call_user_func(fn($name, $options) => '**' . $name . '**: ' . $options[$product->nds], ...self::fields['units']['nds'])
                    ])))
                    ->markdown()
                    ->label('Цены'),
                TextColumn::make('dimensions')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->getStateUsing(fn(Products $product) => new HtmlString(implode('<br>', Arr::map(self::fields['dimensions'], fn($v, $k) => call_user_func(fn($name, $field) => implode(' ', [
                        '**' . $name . '**:',
                        $product->{$k},
                        $product->{$field}
                    ]), ...$v)))))
                    ->markdown()
                    ->label('Размерность')
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
            ]);
    }

    public static function getPages(): array
    {
        return [
            'create' => CreateProducts::route('/create'),
            'edit' => EditProducts::route('/{record}/edit'),
            'index' => ListProducts::route('/')
        ];
    }
}
