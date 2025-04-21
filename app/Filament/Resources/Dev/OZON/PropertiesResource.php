<?php namespace App\Filament\Resources\Dev\OZON;

use App\Filament\Clusters\Dev;
use App\Models\Dev\OZON\Properties;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class PropertiesResource extends Resource
{
    protected static ?string $model = Properties::class;

    protected static ?string $cluster = Dev::class;

    protected static ?string $modelLabel = 'Характеристика';
    protected static ?string $pluralModelLabel = 'Характеристики';

    protected static ?string $navigationLabel = 'Характеристики';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'OZON';

    public static function table(Table $table): Table
    {
        return $table->defaultPaginationPageOption(50)->paginationPageOptions([50, 100, 250])->columns([
            TextColumn::make('id')->label('ID'),
            TextColumn::make('last_request')->label('Дата обработки'),
            TextColumn::make('active')->formatStateUsing(fn($state) => match($state){'Y' => 'Включен', 'D' => 'В ожидании'})->placeholder('Отключен')->label('Активность'),
            TextColumn::make('attribute_complex_id')->placeholder('null')->label('ID complex'),
            TextColumn::make('name')->size(TextColumn\TextColumnSize::ExtraSmall)->wrap()->label('Наименование'),
            TextColumn::make('description')->placeholder('null')->size(TextColumn\TextColumnSize::ExtraSmall)->wrap()->label('Описание'),
            TextColumn::make('type')->size(TextColumn\TextColumnSize::ExtraSmall)->label('Тип'),
            TextColumn::make('is')
                ->getStateUsing(fn(Properties $p) => new HtmlString(implode('<br/>', Arr::map(['Несколько' => 'is_collection', 'Обязательно' => 'is_required', 'Для SKU' => 'is_aspect'], fn($v, $k) => $k.': '.$p->{$v}))))
                ->size(TextColumn\TextColumnSize::ExtraSmall)
                ->markdown()
                ->label('Зависимости'),
            TextColumn::make('max_value_count')->label('Max'),
            TextColumn::make('group')->getStateUsing(fn(Properties $p) => $p->group_id && $p->group_name ? new HtmlString(implode(' | ', [$p->group_id, $p->group_name])) : null)
                ->size(TextColumn\TextColumnSize::ExtraSmall)
                ->placeholder('null')
                ->label('Группа'),
            TextColumn::make('dictionary_id')->placeholder('null')->label('ID словаря'),
            TextColumn::make('category_dependent')->placeholder('null')->label('Зависит от категории'),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListProperties::route('/')];
    }
}
