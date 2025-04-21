<?php namespace App\Filament\Resources\Dev\WB;

use App\Filament\Clusters\Dev;
use App\Filament\Resources\Dev\Traits\Many;
use App\Models\Dev\WB\Properties;
use Filament\Resources\Resource;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PropertiesResource extends Resource
{
    use Many;

    protected static ?string $model = Properties::class;

    protected static ?string $cluster = Dev::class;

    protected static ?string $modelLabel = 'Характеристика';
    protected static ?string $pluralModelLabel = 'Характеристики';

    protected static ?string $navigationLabel = 'Характеристики';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'WB';

    public static function table(Table $table): Table
    {
        return $table->defaultPaginationPageOption(25)->paginationPageOptions([25, 50, 100, 250])->columns([
            TextColumn::make('id')->label('ID'),
            TextColumn::make('last_request')->label('Дата обработки'),
            TextColumn::make('active')->formatStateUsing(fn($state) => match($state){'Y' => 'Включен', 'D' => 'В ожидании'})->placeholder('Отключен')->label('Активность'),
            TextColumn::make('name')->limit(25)->tooltip(fn(TextColumn $column) => call_user_func(fn($state) => strlen($state) > $column->getCharacterLimit() ? $state : null, $column->getState()))->searchable()->label('Наименование'),
            self::many(TextColumn::make('categories'), 'categories', 'name')->label('Категории'),
            self::many(TextColumn::make('values'), 'values', 'value')->label('Значения'),
            TextColumn::make('required')->placeholder('NULL')->label('Обязательное'),
            TextColumn::make('unit')->placeholder('NULL')->label('Unit'),
            TextColumn::make('count')->formatStateUsing(fn($state) => $state > 0 ? $state : '∞')->label('Max count'),
            TextColumn::make('popular')->placeholder('NULL')->label('Популярна'),
            TextColumn::make('type')->formatStateUsing(fn($state) => match ($state){4 => 'Число', 1, 0 => 'Строка'})->label('Тип')
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListProperties::route('/')];
    }
}
