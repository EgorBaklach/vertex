<?php namespace App\Filament\Resources\Dev\WB;

use App\Filament\Clusters\Dev;
use App\Models\Dev\WB\{FBOAmounts, FBSAmounts, Prices, Products, Sizes};
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
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
    protected static ?string $model = Products::class;

    protected static ?string $cluster = Dev::class;

    protected static ?string $modelLabel = 'Товар';
    protected static ?string $pluralModelLabel = 'Товары';

    protected static ?string $navigationLabel = 'Товары';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'WB';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('nmID')->label('nmID')->disabled(),
            TextInput::make('imtID')->label('imtID')->disabled(),
            TextInput::make('nmUUID')->label('nmUUID')->disabled()
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->groups([Group::make('tid')->titlePrefixedWithLabel(false)->getTitleFromRecordUsing(fn (Products $product): string => $product->token->name)->label('API-ключ')])
            ->columns([
                TextColumn::make('ids')->verticalAlignment(VerticalAlignment::Start)
                    ->extraAttributes(['class' => 'list-padding-quarter'])
                    ->state(fn (Products $record): array => [
                        //Action::make('edit')->icon('heroicon-m-pencil-square')->url(fn(): string => self::getUrl('edit', ['record' => $record]))->link(),
                        TextColumn::make('nmID')->record($record)->size(TextColumn\TextColumnSize::ExtraSmall)->description('nmID:', 'above'),
                        TextColumn::make('imtID')->record($record)->size(TextColumn\TextColumnSize::ExtraSmall)->placeholder('imtID: NULL')->description('imtID:', 'above'),
                        TextColumn::make('nmUUID')->record($record)->size(TextColumn\TextColumnSize::ExtraSmall)->placeholder('nmUUID: NULL')->description('nmUUID:', 'above')
                    ])
                    ->listWithLineBreaks()
                    ->color('primary')
                    ->wrap()
                    ->label('Идентификаторы'),
                TextColumn::make('dates')->verticalAlignment(VerticalAlignment::Start)
                    ->extraAttributes(['class' => 'list-padding-quarter'])
                    ->state(fn (Products $product): array => [
                        TextColumn::make('last_request')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->description('Дата импорта:', 'above'),
                        TextColumn::make('createdAt')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->description('Дата создания:', 'above'),
                        TextColumn::make('updatedAt')->record($product)->size(TextColumn\TextColumnSize::ExtraSmall)->description('Дата обновления:', 'above')
                    ])
                    ->listWithLineBreaks()
                    ->label('Даты обслуживания'),
                TextColumn::make('active')->verticalAlignment(VerticalAlignment::Start)->placeholder('Отключен')->sortable()->label('Акт'),
                TextColumn::make('files')->verticalAlignment(VerticalAlignment::Start)
                    ->extraAttributes(['class' => 'list-media-attachments'])
                    ->getStateUsing(fn(Products $product): array => $product->files->map(fn($value) => ($value->type === 'picture' ? '!' : '')."[".Str::limit($value->url, 11, '...')."]({$value->url})")->toArray())
                    ->placeholder('NULL')
                    ->color('success')
                    ->markdown()
                    ->badge()
                    ->label('Фото и видео'),
                TextColumn::make('info')->verticalAlignment(VerticalAlignment::Start)
                    ->extraAttributes(['class' => 'list-padding-quarter'])
                    ->state(fn (Products $product): array => [
                        TextColumn::make('inTrash')->record($product)->formatStateUsing(fn($state) => match($state){'Y' => 'Да'})->placeholder('Нет в корзине')->description('В корзине:', 'above'),
                        TextColumn::make('category')->record($product)->getStateUsing(fn (Products $product) => $product->category->name)->description('Категория:', 'above'),
                        TextColumn::make('brand')->record($product)->description('Бренд:', 'above'),
                        TextColumn::make('vendorCode')->record($product)->description('Артикул продавца:', 'above'),
                        TextColumn::make('title')->record($product)->placeholder('Нет названия')->description('Наименование товара:', 'above'),
                    ])
                    ->listWithLineBreaks()
                    ->wrap()
                    ->label('Данные товара'),
                TextColumn::make('description')->verticalAlignment(VerticalAlignment::Start)
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->placeholder('NULL')
                    ->wrap()
                    ->label('Описание'),
                TextColumn::make('dimensions')->verticalAlignment(VerticalAlignment::Start)
                    ->formatStateUsing(fn($state) => new HtmlString(implode('<br>', Arr::map(explode(':', $state), fn($value, $key) => match($key)
                    {
                        0 => '**Ширина**: '.$value.' см.',
                        1 => '**Высота**: '.$value.' см.',
                        2 => '**Длина**: '.$value.' см.',
                        3 => '**Вес брутто**: '.$value.' кг.',
                        4 => '**isValid**: '.($value ? 'true' : 'false'),
                    }))))
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->markdown()
                    ->label('Размерность упаковки'),
                TextColumn::make('sizes')->verticalAlignment(VerticalAlignment::Start)
                    ->getStateUsing(fn (Products $product) => new HtmlString($product->sizes->map(fn(Sizes $value) => implode('<br>', array_filter([
                        implode(' \ ', array_filter(['<b>SKU</b>', $value->sku, Str::limit($value->techSize, 5), Str::limit($value->wbSize, 5), ...($value->price instanceof Prices ? [strlen($value->price->wbPrice) ? number_format($value->price->wbPrice, 0, '.', '&nbsp;').'&nbsp;₽' : null, '<s>'.number_format($value->price->price, 0, '.', '&nbsp;').'&nbsp;₽</s>'] : [])], 'boolval')),
                        $value->fbo_amounts->count() ? $value->fbo_amounts->map(fn(FBOAmounts $value) => $value->stock->name.': '.$value->amount.' шт.')->unshift('<b>FBO Stocks</b>')->join('<br>') : null,
                        $value->fbs_amounts->count() ? implode('<br>', ['<b>FBS Stocks</b> \ ', ...array_filter($value->fbs_amounts->map(fn(FBSAmounts $value) => $product->tid === $value->stock->tid ? Str::limit($value->stock->name, 30).': '.$value->amount.' шт.' : null)->all(), 'boolval')]) : null
                    ], 'strlen')))->join('<br>---------------------<br>')))
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->label('Размеры'),
                TextColumn::make('chars')->verticalAlignment(VerticalAlignment::Start)
                    ->getStateUsing(function(Products $product)
                    {
                        $properties = []; foreach($product->ppvs as $ppv) $properties[$ppv->property->name][] = trim($ppv->value ?? $ppv->pv->value).(strlen($ppv->property->unit) ? ' '.$ppv->property->unit : '');
                        return implode('<br>', Arr::map($properties, fn($values, $name) => '**'.$name.'**: '.implode(', ', $values)));
                    })
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->placeholder('NULL')
                    ->markdown()
                    ->wrap()
                    ->label('Характеристики')
            ])
            ->filters([
                SelectFilter::make('archived')->default(null)
                    ->options([1 => 'Y', 0 => 'N'])
                    ->query(fn(Builder $query, $data) => !strlen($data['value']) ? null : call_user_func(fn($b) => $b ? $query->where('inTrash', 'Y') : $query->whereNull('inTrash'), $data['value']*1))
                    ->label('В архиве'),
                SelectFilter::make('stocks')->default(null)
                    ->options([1 => 'Y', 0 => 'N'])
                    ->query(fn(Builder $query, $data) => !strlen($data['value']) ? null : $query->where(fn($sub) => call_user_func(fn($m1, $m2) => $sub->{$m1}('sizes.fbo_amounts')->{$m2}('sizes.fbs_amounts'), ...($data['value']*1 ? ['has', 'orHas'] : ['doesntHave', 'doesntHave']))))
                    ->label('Есть остатки'),
                Filter::make('nmID')
                    ->query(fn(Builder $query, $data) => $query->when($data['id'], fn(Builder $query, $id) => call_user_func(fn(...$ids) => count($ids) > 1 ? $query->whereIn('nmID', $ids) : $query->whereLike('nmID', '%'.current($ids).'%'), ...explode(',', $id))))
                    ->form([TextInput::make('id')->label('nmID')]),
                Filter::make('vendorCode')
                    ->query(fn(Builder $query, $data) => $query->when($data['vendorCode'], fn(Builder $query, $id) => call_user_func(fn(...$ids) => count($ids) > 1 ? $query->whereIn('vendorCode', $ids) : $query->whereLike('vendorCode', '%'.current($ids).'%'), ...explode(',', $id))))
                    ->form([TextInput::make('vendorCode')->label('Артикул продавца')]),
                SelectFilter::make('token')
                    ->relationship('token', 'name', fn(Builder $query) => $query->has('wb_products'))
                    ->attribute('tid')
                    ->label('API-ключ')
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
