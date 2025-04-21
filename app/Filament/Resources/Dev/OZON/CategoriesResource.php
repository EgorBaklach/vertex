<?php namespace App\Filament\Resources\Dev\OZON;

use App\Filament\Clusters\Dev;
use App\Models\Dev\OZON\Categories;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoriesResource extends Resource
{
    protected static ?string $model = Categories::class;

    protected static ?string $cluster = Dev::class;

    protected static ?string $modelLabel = 'Категория';
    protected static ?string $pluralModelLabel = 'Категории';

    protected static ?string $navigationLabel = 'Категории';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'OZON';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('parent_id')->orderBy('id'))
            ->defaultPaginationPageOption(50)->paginationPageOptions([50, 100, 250])->columns([
            TextColumn::make('id')->label('ID'),
            TextColumn::make('last_request')->label('Дата обработки'),
            TextColumn::make('active')->formatStateUsing(fn($state) => match($state){'Y' => 'Включен', 'D' => 'В ожидании'})->placeholder('Отключен')->label('Активность'),
            TextColumn::make('disabled')->placeholder('null')->label('Запрет на публикацию товаров'),
            TextColumn::make('name')->label('Наименование'),
            TextColumn::make('childs')->label('Подкатегорий | Типов'),
            TextColumn::make('properties')
                ->getStateUsing(fn (Categories $category) => $category->types->pluck('name')->join(', '))
                ->size(TextColumn\TextColumnSize::ExtraSmall)
                ->placeholder('NULL')
                ->wrap()
                ->label('Типы товаров'),
            TextColumn::make('level')->label('Уровень вложенности'),
            TextColumn::make('parent_id')->label('Parent ID')
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCategories::route('/')];
    }
}
