<?php namespace App\Filament\Resources\Dev\YM;

use App\Contexts\YMCProperties;
use App\Filament\Clusters\Dev;
use App\Helpers\Func;
use App\Helpers\String\Grammar;
use App\Models\Dev\YM\Categories;
use App\Models\Dev\YM\CommodityCodes;
use App\Models\Dev\YM\CP;
use App\Models\Dev\YM\Docs;
use App\Models\Dev\YM\FBSAmounts;
use App\Models\Dev\YM\Notices;
use App\Models\Dev\YM\Prices;
use App\Models\Dev\YM\Products;
use App\Models\Dev\YM\Rating;
use App\Models\Dev\YM\Recommendations;
use App\Models\Dev\YM\SellingPrograms;
use App\Models\Dev\YM\Times;
use App\Services\YM\Traits\RecommendationFields;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Throwable;

class ProductsResource extends Resource
{
    use RecommendationFields;

    protected static ?string $model = Products::class;

    protected static ?string $cluster = Dev::class;

    protected static ?string $modelLabel = 'Товар';
    protected static ?string $pluralModelLabel = 'Товары';

    protected static ?string $navigationLabel = 'Товары';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'YM';

    private const statuses = [
        'card' => [
            'HAS_CARD_CAN_NOT_UPDATE' => 'Активная карточка',
            'HAS_CARD_CAN_UPDATE' => 'Можно дополнить',
            'HAS_CARD_CAN_UPDATE_ERRORS' => 'Изменения не приняты',
            'HAS_CARD_CAN_UPDATE_PROCESSING' => 'Изменения на проверке',
            'NO_CARD_NEED_CONTENT' => 'Создайте карточку',
            'NO_CARD_MARKET_WILL_CREATE' => 'Создаст Маркет',
            'NO_CARD_ERRORS' => 'Не создана из-за ошибки',
            'NO_CARD_PROCESSING' => 'Проверяем данные',
            'NO_CARD_ADD_TO_CAMPAIGN' => 'Разместите товар в магазине'
        ],
        'prices' => [
            'basic' => 'Цена',
            'purchase' => 'Себестоимость',
            'cofinance' => 'Цена для скидок',
            'expenses' => 'Допы на товар'
        ],
        'cc' => [
            'CUSTOMS_COMMODITY_CODE' => 'Код ТНВЕД',
            'IKPU_CODE' => 'ИКПУ'
        ],
        'sp' => [
            'FINE' => 'Доступен',
            'REJECT' => 'Недоступно'
        ],
        'term' => [
            'shelf' => 'Срок годности',
            'life' => 'Срок службы',
            'guarantee' => 'Гарантийный срок'
        ],
        'time' => [
            'HOUR' => ['час', 'часа', 'часов'],
            'DAY' => ['день', 'дня', 'дней'],
            'WEEK' => ['неделя', 'недели', 'недель'],
            'MONTH' => ['месяц', 'месяца', 'месяцев'],
            'YEAR' => ['год', 'года', 'лет'],
        ],
        'rate' => [
            'ACTUAL' => 'Актуальный',
            'UPDATING' => 'Обновляется'
        ],
        'stock' => [
            'AVAILABLE' => 'Доступно к заказу',
            'DEFECT' => 'Брак',
            'EXPIRED' => 'Просрочен',
            'FIT' => 'Годный',
            'FREEZE' => 'Зарезервирован',
            'QUARANTINE' => 'Карантин',
            'UTILIZATION' => 'Утиль'
        ]
    ];

    public static function table(Table $table): Table
    {
        return $table
            ->groups([Group::make('tid')->titlePrefixedWithLabel(false)->getTitleFromRecordUsing(fn (Products $product): string => $product->token->name)->label('API-ключ')])
            ->columns([
                TextColumn::make('ids')->verticalAlignment(VerticalAlignment::Start)
                    ->extraAttributes(['class' => 'list-padding-quarter'])
                    ->state(fn (Products $product): array => [
                        TextColumn::make('last_request')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->description('Дата обновления:', 'above'),
                        TextColumn::make('offerId')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->description('OfferID:', 'above'),
                        TextColumn::make('modelId')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->placeholder('Model_ID is Empty')->description('Model_ID:', 'above'),
                        TextColumn::make('sku_id')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->placeholder('Sku_ID is Empty')->description('Sku_ID:', 'above'),
                        TextColumn::make('barcodes')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->formatStateUsing(fn($state) => str_replace(',', ",\n\r", $state))->placeholder('Barcodes is Empty')->markdown()->description('Barcodes:', 'above'),
                        TextColumn::make('active')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->formatStateUsing(fn($state) => match($state){'Y' => 'Да', 'D' => 'В ожидании'})->placeholder('На удаление')->description('Активен:', 'above'),
                    ])
                    ->listWithLineBreaks()
                    ->wrap()
                    ->label('Обслуживание'),
                TextColumn::make('info')->verticalAlignment(VerticalAlignment::Start)
                    ->extraAttributes(['class' => 'list-padding-quarter'])
                    ->state(fn (Products $product): array => [
                        TextColumn::make('archive')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->formatStateUsing(fn($state) => match($state){'Y' => 'Да'})->placeholder('Нет в архиве')->description('В архиве:', 'above'),
                        TextColumn::make('cardStatus')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->formatStateUsing(fn($state) => self::statuses['card'][$state])->description('Статус карточки:', 'above'),
                        TextColumn::make('category')->record($product->category)->size(TextColumn\TextColumnSize::ExtraSmall)->getStateUsing(fn (Categories $category) => $category->name)->description('Категория:', 'above'),
                        TextColumn::make('manufacturerCountries')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->formatStateUsing(fn($state) => str_replace(',', ",\n\r", $state))->placeholder('Страна производителя не указана')->description('Страна производителя:', 'above'),
                        TextColumn::make('vendor')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->placeholder('Производитель не указан')->description('Производитель:', 'above'),
                        TextColumn::make('boxCount')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->description('Кол-во грузовых мест:', 'above'),
                    ])
                    ->listWithLineBreaks()
                    ->wrap()
                    ->label('Данные товара'),
                TextColumn::make('files')->verticalAlignment(VerticalAlignment::Start)
                    ->extraAttributes(['class' => 'list-media-attachments'])
                    ->state(fn(Products $product): array => $product->docs->map(fn(Docs $value) => ($value->type === 'picture' ? '!' : '')."[".Str::limit($value->value, 11, '...')."]({$value->value})")->toArray())
                    ->placeholder('NULL')
                    ->color('success')
                    ->markdown()
                    ->badge()
                    ->label('Фото и видео'),
                TextColumn::make('text')->verticalAlignment(VerticalAlignment::Start)
                    ->extraAttributes(['class' => 'list-padding-quarter'])
                    ->state(fn (Products $product) => [
                        TextColumn::make('name')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->description('Наименование:', 'above'),
                        TextColumn::make('skuName')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->placeholder('Название карточки не указано')->description('Название карточки товара:', 'above'),
                        TextColumn::make('modelName')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->placeholder('Название модели не указано')->description('Название модели на Маркете:', 'above'),
                        TextColumn::make('description')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->description('Описание', 'above'),
                        TextColumn::make('tags')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->formatStateUsing(fn($state) => str_replace(',', ", ", $state))->description('Теги:', 'above')
                    ])
                    ->listWithLineBreaks()
                    ->wrap()
                    ->label('Описание'),
                TextColumn::make('price')->verticalAlignment(VerticalAlignment::Start)
                    ->getStateUsing(fn(Products $product) => new HtmlString(implode('<br>', [
                        ...$product->prices->map(fn(Prices $price) => implode(' ', array_filter([
                            '**'.self::statuses['prices'][$price->type].'**:',
                            number_format($price->value, 0, '.', '&nbsp;').'&nbsp;₽',
                            $price->discountBase ? '~~'.number_format($price->discountBase, 0, '.', '&nbsp;').'&nbsp;₽~~' : null
                        ]))),
                        '---------------------',
                        ...$product->fbs_amounts->map(fn(FBSAmounts $amounts) => implode('<br>', [
                            '**FBS / '.$amounts->stock->token->name.'**',
                            self::statuses['stock'][$amounts->type].': **'.$amounts->count.'** шт.',
                            '---------------------'
                        ])),
                        ...$product->selling_programs->map(fn(SellingPrograms $program) => '**'.$program->program.'**: '.self::statuses['sp'][$program->status]),
                        '---------------------',
                        ...Arr::map(explode(',', $product->weightDimensions), fn($value, $key) => match($key)
                        {
                            0 => '**Длина**: '.$value.' см.',
                            1 => '**Ширина**: '.$value.' см.',
                            2 => '**Высота**: '.$value.' см.',
                            3 => '**Вес**: '.$value.' кг.'
                        }),
                        '---------------------',
                        ...$product->commodity_codes->map(fn(CommodityCodes $cc) => '**'.self::statuses['cc'][$cc->type].'**: '.$cc->code),
                        ...$product->times->map(fn(Times $time) => '**'.self::statuses['term'][$time->type].'**: '.$time->period.' '.Grammar::plural($time->period, self::statuses['time'][$time->unit]))
                    ])))
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->markdown()
                    ->wrap()
                    ->label('Цена, Остатки, Габариты, Код'),
                TextColumn::make('chars')->verticalAlignment(VerticalAlignment::Start)
                    ->getStateUsing(fn(Products $product) => Func::call(App::make(YMCProperties::class)($product), fn(?string $html) => strlen($html) ? new HtmlString($html) : null))
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->placeholder('NULL')
                    ->markdown()
                    ->wrap()
                    ->label('Характеристики'),
                TextColumn::make('marketing')->verticalAlignment(VerticalAlignment::Start)
                    ->getStateUsing(fn(Products $product) => !$product->rating instanceof Rating ? null : new HtmlString(implode('<br>', [
                        '**Рейтинг:**',
                        self::statuses['rate'][$product->rating->status].': **'.$product->rating->rating.'**%',
                        'Средний рейтинг карточки: **'.$product->rating->average.'**%',
                        ...array_filter(Arr::map(self::recFields, fn($value, $field) => Func::call($product->recommendations->{$field} ?? null, fn($r) => !$r ? null : implode('<br>', array_filter([
                            '---------------------',
                            '**'.$field.'**',
                            $value,
                            '**Баллы рейтинга: '.($r & 0x7F).'%**',
                            Func::call($r >> 7, fn($p) => $p ? '**Выполнено: '.$p.'%**' : null)
                        ])))))
                    ])))
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->placeholder('NULL')
                    ->markdown()
                    ->wrap()
                    ->label('Рекомендации'),
                TextColumn::make('notices')->verticalAlignment(VerticalAlignment::Start)
                    ->getStateUsing(fn(Products $product) => !$product->notices->count() ? null : new HtmlString($product->notices->map(fn(Notices $notice) =>
                        '<b>'.$notice->type.'</b>: '.$notice->message.'<br>'.$notice->comment
                    )->join('<br>---------------------<br>')))
                    ->placeholder('NULL')
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->wrap()
                    ->label('Уведомления')
            ])
            ->filters([
                SelectFilter::make('active')
                    ->options(['Y' => 'Включен', 'D' => 'В ожидании', 'NULL' => 'На удаление'])
                    ->query(fn(Builder $query, $data) => !$data['value'] ? null : ($data['value'] !== 'NULL' ? $query->where('active', $data['value']) : $query->whereNull('active')))
                    ->label('Активность'),
                Filter::make('offerId')
                    ->query(fn(Builder $query, $data) => $query->when($data['offerId'], fn(Builder $query, $id) => call_user_func(fn(...$ids) => count($ids) > 1 ? $query->whereIn('offerId', $ids) : $query->whereLike('offerId', '%'.current($ids).'%'), ...explode(',', $id))))
                    ->form([TextInput::make('offerId')->label('OfferID')]),
            ])
            ->paginationPageOptions([25, 50, 100, 250])
            ->defaultPaginationPageOption(25)
            ->defaultGroup('tid');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/')
        ];
    }
}
