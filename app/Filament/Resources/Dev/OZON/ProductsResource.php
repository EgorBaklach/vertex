<?php namespace App\Filament\Resources\Dev\OZON;

use App\Filament\Clusters\Dev;
use App\Helpers\Func;
use App\Helpers\String\Shower;
use App\Models\Dev\OZON\{Categories,
    Commissions,
    Errors,
    FBOAmounts,
    FBSAmounts,
    Indexes,
    Prices,
    Products,
    Properties,
    Statuses};
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
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ProductsResource extends Resource
{
    protected static ?string $model = Prices::class;

    protected static ?string $cluster = Dev::class;

    protected static ?string $modelLabel = 'Товар';
    protected static ?string $pluralModelLabel = 'Товары';

    protected static ?string $navigationLabel = 'Товары';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'OZON';

    private const exclude_props = [
        'queue' => [11254, 13164],
        'break' => [9024, 8292, 10289]
    ];

    public static function table(Table $table): Table
    {
        return $table
            ->groups([Group::make('token_id')->titlePrefixedWithLabel(false)->getTitleFromRecordUsing(fn (Prices $price): string => $price->token->name)->label('API-ключ')])
            ->columns([
                TextColumn::make('ids')->verticalAlignment(VerticalAlignment::Start)
                    ->extraAttributes(['class' => 'list-padding-quarter'])
                    ->state(fn (Prices $price): array => [
                        TextColumn::make('created_at')->record($price)->size(TextColumn\TextColumnSize::ExtraSmall)->description('Дата создания:', 'above'),
                        TextColumn::make('updated_at')->record($price->product->statuses)->size(TextColumn\TextColumnSize::ExtraSmall)->description('Дата обновления:', 'above'),
                        TextColumn::make('last_request')->record($price)->size(TextColumn\TextColumnSize::ExtraSmall)->description('Дата импорта:', 'above'),
                        TextColumn::make('offer_id')->record($price->product)
                            ->getStateUsing(fn(Products $product) => new HtmlString(preg_replace('/([_x])+/', '<br>$1', $product->offer_id)))
                            ->size(TextColumn\TextColumnSize::ExtraSmall)->description('Offer ID:', 'above'),
                        TextColumn::make('sku')->record($price)->size(TextColumn\TextColumnSize::ExtraSmall)->description('Sku ID:', 'above'),
                        TextColumn::make('id')->record($price->product)->size(TextColumn\TextColumnSize::ExtraSmall)->description('Product ID:', 'above'),
                        TextColumn::make('barcode')->record($price->product)->size(TextColumn\TextColumnSize::ExtraSmall)->description('Barcode:', 'above'),
                        TextColumn::make('active')->record($price)->formatStateUsing(fn($state) => match($state){'Y' => 'Да', 'D' => 'В ожидании'})->placeholder('На удаление')->size(TextColumn\TextColumnSize::ExtraSmall)->description('Активен:', 'above'),
                    ])
                    ->listWithLineBreaks()
                    ->color('primary')
                    ->wrap()
                    ->label('Обслуживание'),
                TextColumn::make('statuses')->verticalAlignment(VerticalAlignment::Start)
                    ->extraAttributes(['class' => 'list-padding-quarter'])
                    ->state(fn (Prices $price): array => Func::call($price->product->statuses, fn(Statuses $status) => [
                        TextColumn::make('status_name')->record($status)->formatStateUsing(fn($state) => !$price->fbo && !$price->fbs && strcasecmp($state, 'Продается') === 0 ? 'Нет на складах' : $state),
                        TextColumn::make('status_description')->record($status)->getStateUsing(fn(Statuses $status) => $status->status_description ?? 'Без описания')->size(TextColumn\TextColumnSize::ExtraSmall)->description('Описание статуса:', 'above'),
                        TextColumn::make('status')->record($status)->getStateUsing(fn(Statuses $status) => '**'.$status->status.'**')->markdown()->size(TextColumn\TextColumnSize::ExtraSmall)->description('Статус товара:', 'above'),
                        TextColumn::make('is_created')->record($status)->formatStateUsing(fn($state) => match($state){'Y' => 'Да'})->placeholder('Ошибки в создании товара')->size(TextColumn\TextColumnSize::ExtraSmall)->description('Создан корректно:', 'above'),
                        TextColumn::make('moderate_status')->record($status)->placeholder('Не промодерирован')->formatStateUsing(fn($state) => match($state)
                        {
                            'approved' => 'Одобрен',
                            'postmoderation' => 'Постмодерация',
                            'declined' => 'Отклонен',
                            'in-moderating' => 'Модерируется',
                            'failed' => 'Ошибка модерации',
                            default => $state
                        })
                        ->size(TextColumn\TextColumnSize::ExtraSmall)->description('Статус модерации:', 'above'),
                        TextColumn::make('status_failed')->record($status)->getStateUsing(fn(Statuses $status) => $status->status_failed ? '**'.$status->status_failed.'**' : 'Ошибок нет')->markdown()->size(TextColumn\TextColumnSize::ExtraSmall)->description('Статус ошибки:', 'above'),
                        TextColumn::make('validation_status')->record($status)->size(TextColumn\TextColumnSize::ExtraSmall)->description('Статус валидации:', 'above'),
                        TextColumn::make('status_tooltip')->record($status)->wrap()->size(TextColumn\TextColumnSize::ExtraSmall)->description('Подсказка:', 'above'),
                    ]))
                    ->listWithLineBreaks()
                    ->label('Статусы'),
                TextColumn::make('files')->verticalAlignment(VerticalAlignment::Start)
                    ->extraAttributes(['class' => 'list-media-attachments'])
                    ->getStateUsing(fn(Prices $price): array => $price->product->files->map(fn($value) => ($value->type === 'picture' ? '!' : '')."[".Str::limit($value->url, 11, '...')."]({$value->url})")->toArray())
                    ->placeholder('NULL')
                    ->color('success')
                    ->markdown()
                    ->badge()
                    ->label('Фото и видео'),
                TextColumn::make('info')->verticalAlignment(VerticalAlignment::Start)
                    ->extraAttributes(['class' => 'list-padding-quarter'])
                    ->state(fn (Prices $price): array => [
                        TextColumn::make('archived')->record($price->product)->formatStateUsing(fn($state) => match($state){'Y' => 'Да'})->placeholder('Нет в архиве')->description('В архиве:', 'above'),
                        TextColumn::make('category')->record($price->product->category)->getStateUsing(fn (Categories $category) => $category->name)->description('Категория:', 'above'),
                        TextColumn::make('type')->record($price->product)->getStateUsing(fn (Products $products) => $products->type ? $products->type->name : 'null')->description('Тип товара:', 'above'),
                        TextColumn::make('name')->record($price->product)->wrap()->description('Наименование товара:', 'above'),
                        TextColumn::make('source')->record($price)->wrap()->description('Схема продажи:', 'above'),
                        TextColumn::make('model_id')->record($price->product)
                            ->getStateUsing(fn(Products $product) => new HtmlString('<a href="'.urldecode(self::getUrl('index', ['tableFilters' => ['model_id' => ['model_id' => $product->model_id], 'token' => ['value' => $product->token_id]]])).'">'.$product->model_id.' - '.$product->model_count.' тов.</a>'))
                            ->icon('heroicon-o-link')
                            ->color('primary')
                            ->size(TextColumn\TextColumnSize::ExtraSmall)
                            ->description('Модель:', 'above'),
                    ])
                    ->listWithLineBreaks()
                    ->wrap()
                    ->label('Данные товара'),
                TextColumn::make('price')->verticalAlignment(VerticalAlignment::Start)
                    ->getStateUsing(fn(Prices $price) => new HtmlString(implode('<br>', [
                        '**Market**: '.($price->marketing_price ? number_format($price->marketing_price, 0, '.', '&nbsp;').'&nbsp;₽' : 'NULL'),
                        '**Minimal**: '.($price->min_price ? number_format($price->min_price, 0, '.', '&nbsp;').'&nbsp;₽' : 'NULL'),
                        '**Old**: '.($price->old_price ? number_format($price->old_price, 0, '.', '&nbsp;').'&nbsp;₽' : 'NULL'),
                        '**Price**: '.($price->price ? number_format($price->price, 0, '.', '&nbsp;').'&nbsp;₽' : 'NULL'),
                        '---------------------',
                        ...Func::call($price->product, fn(Products $product) => [
                            ...Arr::map(explode(':', $product->dimensions), fn($value, $key) => match($key)
                            {
                                0 => '**Глубина**: '.$value.' '.$product->dimension_unit,
                                1 => '**Ширина**: '.$value.' '.$product->dimension_unit,
                                2 => '**Высота**: '.$value.' '.$product->dimension_unit
                            }),
                            '**Вес**: '.$product->weight.' '.$product->weight_unit
                        ]),
                        ...($price->price_index === 'WITHOUT_INDEX' ? [] : Func::call($price->product, fn(Products $product) => $product->indexes->map(fn(Indexes $index) => implode('<br>', [
                            implode(' \ ', ['<b>'.ucfirst($index->type).'</b>', $index->active === 'Y' ? 'Active' : 'NULL']),
                            'Минимальная цена: '.number_format($index->minimal_price, 0, '.', '&nbsp;').'&nbsp;₽',
                            'Значение индекса цены: '.$index->price_index_value,
                        ]))->unshift('---------------------', $price->price_index)->all()))
                    ])))
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->markdown()
                    ->label('Цены и Индексы'),
                TextColumn::make('commissions')->verticalAlignment(VerticalAlignment::Start)
                    ->getStateUsing(fn(Prices $price) => Func::call($price->product, fn(Products $product) => new HtmlString($product->commissions->map(fn(Commissions $commission) => implode('<br>', [
                        implode(' \ ', ['<b>'.$commission->sale_schema.'</b>', $commission->active === 'Y' ? 'Active' : 'NULL']),
                        'Стоимость доставки: '.($commission->delivery_amount ? number_format($commission->delivery_amount, 2, '.', '&nbsp;').'&nbsp;₽' : '<b>null</b>'),
                        'Процент комиссии: '.($commission->percent ? $commission->percent.'&nbsp;%' : '<b>null</b>'),
                        'Стоимость возврата: '.($commission->return_amount ? number_format($commission->return_amount, 2, '.', '&nbsp;').'&nbsp;₽' : '<b>null</b>'),
                        'Сумма комиссии: '.($commission->value ? number_format($commission->value, 2, '.', '&nbsp;').'&nbsp;₽' : '<b>null</b>')
                    ]))->join('<br>---------------------<br>'))))
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->label('Комиссии'),
                TextColumn::make('chars')->verticalAlignment(VerticalAlignment::Start)
                    ->getStateUsing(fn(Prices $price) => Func::call($price->product, function(Products $product)
                    {
                        $properties = []; $callback = fn(Properties $property, $values) => '**'.$property->name.'**: '.implode(', ', $values).($property->is_aspect === 'Y' ? ' - <span style="color: rgb(var(--primary-600))">*ASPECT*</span>' : '');

                        foreach($product->ppvs as $ppv) if(!in_array($ppv->property_id, self::exclude_props['queue'])) [
                            $properties[$ppv->property_id]['property'] ??= $ppv->property,
                            $properties[$ppv->property_id]['values'][] = Func::call(trim($ppv->value ?? $ppv->pv->value), fn($v) => in_array($ppv->property_id, self::exclude_props['break']) ? preg_replace('/([_x])+/', '<br>$1', $v) : $v)
                        ];

                        return implode('<br>', Arr::map($properties, fn($values) => call_user_func($callback, ...$values)));
                    }))
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->placeholder('NULL')
                    ->markdown()
                    ->wrap()
                    ->label('Характеристики'),
                TextColumn::make('stocks')->verticalAlignment(VerticalAlignment::Start)
                    ->getStateUsing(fn(Prices $price) => !$price->fbo && !$price->fbs ? null : new HtmlString(implode('<br>---------------------<br>', array_filter(Arr::map(['fbo', 'fbs'], fn($v) => !$price->{$v} ? null :
                        $price->{$v.'_amounts'}->map(fn(FBOAmounts|FBSAmounts $amount) => '**'.$amount->stock->name.'**: '.implode('&nbsp;', [$amount->type, '\\', $amount->amount, 'шт.']))
                            ->unshift('**'.strtoupper($v).'**:&nbsp;'.str_replace(' ', '&nbsp;', $price->{$v}))->join('<br>')), 'boolval'))))
                    ->placeholder('NULL')
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->markdown()
                    ->wrap()
                    ->label('Остатки'),
                TextColumn::make('errors')->verticalAlignment(VerticalAlignment::Start)
                    ->getStateUsing(fn(Prices $price) => Func::call($price->product, fn(Products $product) => $product->errors->count() ? new HtmlString($product->errors->map(fn(Errors $error) => implode('<br>', array_filter([
                        '<b>'.($error->level).'</b>',
                        '<b>Код ошибки</b>: '.($error->code ?? 'Code is Empty'),
                        '<b>Поле с ошибкой</b>: '.($error->field ?? 'Field is Empty'),
                        '<b>Статус товара</b>: '.$error->state,
                        '<b>Описание ошибки</b>: '.($error->description ?? $error->message ?? 'Description is Empty'),
                        call_user_func(fn(string $p) => strlen($p) ? '<b>Где была допущена ошибка</b>: '.$p : null, implode('<br>', array_filter([
                            Func::call(json_decode($error->message, true, 512, JSON_BIGINT_AS_STRING), fn($message) => $message ? Shower::printPre($message, 'Message data') : null),
                            strlen($error->params) ? Shower::printPre(json_decode($error->params, true, 512, JSON_BIGINT_AS_STRING), 'Parameters data') : null
                        ], 'boolval')))
                    ], 'boolval')))->join('<br>---------------------<br>')) : null))
                    ->placeholder('NULL')
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->wrap()
                    ->label('Ошибки')
            ])
            ->filters([
                SelectFilter::make('stocks')->default(null)
                    ->options([1 => 'Y', 0 => 'N'])
                    ->query(fn(Builder $query, $data) => !strlen($data['value']) ? null : $query->where(fn($sub) => call_user_func(fn($m1, $m2) => $sub->{$m1}('fbo')->{$m2}('fbs'), ...($data['value']*1 ? ['whereNotNull', 'orWhereNotNull'] : ['whereNull', 'whereNull']))))
                    ->label('Есть остатки'),
                SelectFilter::make('archived')->default(null)
                    ->options([1 => 'Y', 0 => 'N'])
                    ->query(fn(Builder $query, $data) => !strlen($data['value']) ? null : Func::call($data['value']*1, fn($b) => $query->whereHas('product', fn(Builder $sub) => $b ? $sub->where('archived', 'Y') : $sub->whereNull('archived'))))
                    ->label('В архиве'),
                SelectFilter::make('errors')->default(null)
                    ->options([1 => 'Y', 0 => 'N'])
                    ->query(fn(Builder $query, $data) => !strlen($data['value']) ? null : Func::call($data['value']*1, fn($b) => $query->whereHas('product', fn(Builder $sub) => $b ? $sub->whereHas('errors') : $sub->whereDoesntHave('errors'))))
                    ->label('С ошибками'),
                Filter::make('sku')
                    ->query(fn(Builder $query, $data) => $query->when($data['sku'], fn(Builder $query, $id) => call_user_func(fn(...$ids) => count($ids) > 1 ? $query->whereIn('sku', $ids) : $query->whereLike('sku', '%'.current($ids).'%'), ...explode(',', $id))))
                    ->form([
                        TextInput::make('sku')->label('SKU')
                    ]),
                Filter::make('offer_id')
                    ->query(fn(Builder $query, $data) => $query->when($data['offer_id'], fn(Builder $query, $id) => $query->whereHas('product', fn(Builder $builder) => call_user_func(fn(...$ids) => count($ids) > 1 ? $builder->whereIn('offer_id', $ids) : $builder->whereLike('offer_id', '%'.current($ids).'%'), ...explode(',', $id)))))
                    ->form([
                        TextInput::make('offer_id')->label('Артикул продавца')
                    ]),
                Filter::make('product_id')
                    ->query(fn(Builder $query, $data) => $query->when($data['product_id'], fn(Builder $query, $id) => $query->whereHas('product', fn(Builder $builder) => call_user_func(fn(...$ids) => count($ids) > 1 ? $builder->whereIn('id', $ids) : $builder->whereLike('id', '%'.current($ids).'%'), ...explode(',', $id)))))
                    ->form([
                        TextInput::make('product_id')->label('PRODUCT ID')
                    ]),
                Filter::make('model_id')
                    ->query(fn(Builder $query, $data) => $query->when($data['model_id'], fn(Builder $query, $id) => $query->whereHas('product', fn(Builder $builder) => $builder->where('model_id', $id))))
                    ->form([
                        TextInput::make('model_id')->label('MODEL ID')
                    ]),
                SelectFilter::make('token')
                    ->relationship('token', 'name', fn(Builder $query) => $query->has('ozon_products'))
                    ->attribute('token_id')
                    ->label('API-ключ')
            ])
            ->paginationPageOptions([25, 50, 100, 250])
            ->defaultPaginationPageOption(25)
            ->defaultGroup('token_id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/')
        ];
    }
}
