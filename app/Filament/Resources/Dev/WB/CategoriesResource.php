<?php namespace App\Filament\Resources\Dev\WB;

use App\Filament\Clusters\Dev;
use App\Models\Dev\WB\Categories;
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
    protected static ?string $navigationGroup = 'WB';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('parentId')->orderBy('id'))
            ->defaultPaginationPageOption(10)->paginationPageOptions([10, 25, 50, 100, 250])->columns([
            TextColumn::make('id')->label('ID'),
            TextColumn::make('last_request')->label('Дата обработки'),
            TextColumn::make('active')->formatStateUsing(fn($state) => match($state){'Y' => 'Включен', 'D' => 'В ожидании'})->placeholder('Отключен')->label('Активность'),
            TextColumn::make('childs')->label('Подкатегорий'),
            TextColumn::make('name')->label('Наименование'),
            TextColumn::make('properties')
                ->getStateUsing(fn (Categories $category) => $category->properties->pluck('name')->join(', '))
                ->size(TextColumn\TextColumnSize::ExtraSmall)
                ->placeholder('NULL')
                ->wrap()
                ->label('Справочники'),
            TextColumn::make('parentId')->label('Parent ID')
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCategories::route('/')];
    }
}
