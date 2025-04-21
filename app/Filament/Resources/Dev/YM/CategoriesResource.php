<?php namespace App\Filament\Resources\Dev\YM;

use App\Filament\Clusters\Dev;
use App\Models\Dev\YM\Categories;
use App\Models\Dev\YM\CP;
use App\Models\Dev\YM\Properties;
use Filament\Resources\Resource;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class CategoriesResource extends Resource
{
    protected static ?string $model = Categories::class;

    protected static ?string $cluster = Dev::class;

    protected static ?string $modelLabel = 'Категория';
    protected static ?string $pluralModelLabel = 'Категории';

    protected static ?string $navigationLabel = 'Категории';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'YM';

    private const limit = 50;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('level')->orderBy('pid')->orderBy('id'))
            ->defaultPaginationPageOption(50)->paginationPageOptions([50, 100, 250])->columns([
                TextColumn::make('ids')->verticalAlignment(VerticalAlignment::Start)
                    ->extraAttributes(['class' => 'list-padding-quarter'])
                    ->state(fn (Categories $category): array => [
                        TextColumn::make('id')->record($category)->description('ID:', 'above'),
                        TextColumn::make('last_request')->record($category)->description('Дата импорта:', 'above'),
                    ])
                    ->listWithLineBreaks()
                    ->label('Идентификатор'),
                TextColumn::make('active')->verticalAlignment(VerticalAlignment::Start)
                    ->formatStateUsing(fn($state) => match($state){'Y' => 'Включен', 'D' => 'В ожидании'})
                    ->placeholder('Отключен')
                    ->label('Активность'),
                TextColumn::make('name')->verticalAlignment(VerticalAlignment::Start)->label('Наименование'),
                TextColumn::make('properties')->verticalAlignment(VerticalAlignment::Start)
                    ->getStateUsing(function(Categories $category)
                    {
                        if(!$category->cp->count()) return null; /** @var CP $cp */ $rows = ['cnt' => 0, 'values' => []];

                        foreach($category->cp->all() as $cp)
                        {
                            if(array_key_exists($hash = md5(trim($cp->name)), $rows['values'])) continue;

                            if(count($rows['values']) <= self::limit) $rows['values'][$hash] = $cp->name; $rows['cnt']++;
                        }

                        return new HtmlString(implode(", ", $rows['values']).($rows['cnt'] > self::limit ? '<br><br><b>Всего: '.$rows['cnt'].'</b>' : ''));
                    })
                    ->size(TextColumn\TextColumnSize::ExtraSmall)
                    ->placeholder('NULL')
                    ->wrap()
                    ->label('Характеристики'),
                TextColumn::make('level')->verticalAlignment(VerticalAlignment::Start)->label('Уровень вложенности'),
                TextColumn::make('pid')->verticalAlignment(VerticalAlignment::Start)->placeholder('NULL')->label('Parent ID'),
                TextColumn::make('childs')->verticalAlignment(VerticalAlignment::Start)->label('Подкатегорий')
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCategories::route('/')];
    }
}
