<?php namespace App\Filament\Resources\Dev\OZON;

use App\Filament\Clusters\Dev;
use App\Models\Dev\OZON\Types;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TypesResource extends Resource
{
    protected static ?string $model = Types::class;

    protected static ?string $cluster = Dev::class;

    protected static ?string $modelLabel = 'Тип товара';
    protected static ?string $pluralModelLabel = 'Типы товаров';

    protected static ?string $navigationLabel = 'Типы товаров';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'OZON';

    public static function table(Table $table): Table
    {
        return $table->defaultPaginationPageOption(50)->paginationPageOptions([50, 100, 250])->columns([
            TextColumn::make('id')->label('ID'),
            TextColumn::make('last_request')->label('Дата обработки'),
            TextColumn::make('active')->formatStateUsing(fn($state) => match($state){'Y' => 'Включен', 'D' => 'В ожидании'})->placeholder('Отключен')->label('Активность'),
            TextColumn::make('disabled')->placeholder('null')->label('Запрет на публикацию товаров'),
            TextColumn::make('name')->label('Наименование'),
            TextColumn::make('properties')
                ->getStateUsing(fn (Types $types) => $types->categories->pluck('name')->join(', '))
                ->size(TextColumn\TextColumnSize::ExtraSmall)
                ->placeholder('NULL')
                ->wrap()
                ->label('Категории')
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListTypes::route('/')];
    }
}
