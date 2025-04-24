<?php namespace App\Filament\Resources\Management;

use App\Filament\Clusters\Management;
use App\Filament\Forms\Components\Autocomplete;
use App\Filament\Resources\Management\Pages\{CreateProducts, EditProducts, ListProducts};
use App\Models\Dev\OZON\CT;
use App\Models\Management\{Categories, Designs\Patterns, Products};
use Filament\Forms\{Components\Select, Components\TextInput, Form, Get, Set};
use Filament\Resources\Resource;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
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

    private static array $dynamics = [];

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

    private static function category(Categories $category): string
    {
        return implode(' / ', Arr::map(array_keys(App::get('markets')), fn($v) => $v.' - <b>'.$category->{$v}->name.'</b>'));
    }

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
            ->schema(fn() => [
                Select::make('active')->options(['Y' => 'Y'])->placeholder('N')->columnSpan(self::grid[2])->default('Y')->reactive()->label('Вкл:'),
                Autocomplete::make('name')->setOptions(App::make(Patterns::class))->columnSpan(self::grid[10])->required()->label('Наименование:'),
                Select::make('cid')
                    ->getOptionLabelFromRecordUsing(fn (Categories $category) => self::category($category))
                    ->relationship('category')
                    ->columnSpanFull()
                    ->searchable()
                    ->allowHtml()
                    ->required()
                    ->reactive()
                    ->preload()
                    ->afterStateHydrated(function($state)
                    {
                        if($state === 1) self::$dynamics = ['test2', 'test3'];
                    })
                    ->afterStateUpdated(function()
                    {
                        self::$dynamics = ['test2', 'test3'];
                    })
                    ->createOptionForm(fn(Form $form) => $form
                        ->columns(6)
                        ->schema([
                            ...Arr::map(App::get('markets'), fn($v, $k) => call_user_func($v['extender'] ?? fn($select) => $select, Select::make($v['field'])
                                ->getOptionLabelFromRecordUsing(fn (Model $model) => call_user_func($v['option'], $model))
                                ->relationship($k, 'name', fn(Builder $query, Get $get) => call_user_func($v['query'], $query->where('active', 'Y'), $get))
                                ->label(ucfirst($k).':')
                                ->optionsLimit(50)
                                ->columnSpanFull()
                                ->searchable()
                                ->required()
                                ->live())
                            )
                        ])
                    )
                    ->createOptionUsing(static function (Select $component, array $data, Form $form)
                    {
                        $data['ozon_cid'] = $data['ozon_cid'] ?? CT::query()->where('tid', $data['ozon_tid'])->pluck('cid')->first();

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

                ...Arr::map(self::fields['units'], fn($v, $k) => call_user_func(fn($name, $options, $cols) => Select::make($k)->options($options)->required()->columnSpan($cols)->label($name.':'), ...$v)),
                ...Arr::map(self::fields['dimensions'], fn($v, $k) => call_user_func(fn($name) => TextInput::make($k)->numeric()->required()->columnSpan(self::grid[3])->label($name.':'), ...$v)),

                ...Arr::map(self::$dynamics, fn($field) => TextInput::make($field))
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
                    ->getStateUsing(fn(Products $product) => self::category($product->category))
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->html()
                    ->wrap()
                    ->label('Категория'),
                TextColumn::make('info')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->getStateUsing(fn(Products $product) => new HtmlString(implode('<br>', [
                        '**Наименование**: '.$product->name,
                        '**Артикул**: '.$product->article,
                        '**Баркод**: '.($product->barcode ?? '*Не установлен*'),
                        '---------------------',
                        '**Дата создания**: '.$product->create_date,
                        '**Активность**: '.($product->active ?? '*N*')
                    ])))
                    ->markdown()
                    ->label('Информация о товаре'),
                TextColumn::make('price')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->getStateUsing(fn(Products $product) => new HtmlString(implode('<br>', [
                        '**Цена с учетом скидки**: '.number_format($product->discount_price, 0, '.', '&nbsp;').'&nbsp;₽',
                        '**Цена до скидок**: '.($product->price ? number_format($product->price, 0, '.', '&nbsp;').'&nbsp;₽' : '*Не установлена*'),
                        call_user_func(fn($name, $options) => '**'.$name.'**: '.$options[$product->nds], ...self::fields['units']['nds'])
                    ])))
                    ->markdown()
                    ->label('Цены'),
                TextColumn::make('dimensions')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->getStateUsing(fn(Products $product) => new HtmlString(implode('<br>', Arr::map(self::fields['dimensions'], fn($v, $k) => call_user_func(fn($name, $field) => implode(' ', [
                        '**'.$name.'**:',
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
