<?php namespace App\Filament\Resources\Management\Design;

use App\Filament\Clusters\Management;
use App\Helpers\Arr;
use App\Models\Management\Designs\Colors;
use App\Models\Management\Designs\Designs;
use App\Models\Management\Designs\Groups;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class DesignsResource extends Resource
{
    protected static ?string $model = Designs::class;

    protected static ?string $cluster = Management::class;

    protected static ?string $modelLabel = 'Дизайн';
    protected static ?string $pluralModelLabel = 'Дизайны';

    protected static ?string $navigationLabel = 'Дизайны';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Designs';

    private const variants = [
        'colors' => [
            'ozon' => 'Ozon',
            'yandex' => 'Yandex Market'
        ],
        'groups' => [
            'first' => 'Первая',
            'second' => 'Вторая',
            'third' => 'Третья'
        ]
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('article')->label('Артикул')->required(),
            TextInput::make('number')->label('Номер')->required(),
            Select::make('is_block')->options(['Y' => 'Да'])->placeholder('Нет')->reactive()->label('Заблокировать'),
            TextInput::make('name')->label('Наименование')->required(),

            Repeater::make('colors')
                ->relationship()
                ->schema([
                    Select::make('market')->options(self::variants['colors'])->required()->label('Маркет'),
                    TextInput::make('value')->required()->label('Значение'),
                ])
                ->defaultItems(0)
                ->label('Цвета для маркетплейса')
                ->columns()
                ->columnSpanFull(),

            Repeater::make('groups')
                ->relationship()
                ->schema([
                    Select::make('point')->options(self::variants['groups'])->required()->label('Группа'),
                    TextInput::make('value')->required()->label('Значение'),
                ])
                ->defaultItems(0)
                ->label('Группы для дизайна')
                ->columns()
                ->columnSpanFull()
        ]);
    }

    public static function table(Table $table): Table
    {
        $toggle = [
            'block' => fn(Collection $records, bool $state = null) => Arr::map($records->all(), fn(Designs $design) => $design->update(['is_block' => $state ? 'Y' : null]))
        ];

        return $table
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([25, 50, 100, 250])
            ->columns([
                TextColumn::make('article')->searchable()->verticalAlignment(VerticalAlignment::Start)->label('Артикул'),
                TextColumn::make('number')->verticalAlignment(VerticalAlignment::Start)->label('Number'),
                TextColumn::make('is_block')->verticalAlignment(VerticalAlignment::Start)->placeholder('NULL')->sortable()->label('Блокировка'),
                TextColumn::make('name')->searchable()->verticalAlignment(VerticalAlignment::Start)->placeholder('NULL')->wrap()->label('Наименование'),
                TextColumn::make('colors')->verticalAlignment(VerticalAlignment::Start)
                    ->getStateUsing(fn(Designs $design) => !$design->colors->count() ? null : new HtmlString($design->colors->map(fn(Colors $color) =>
                        '<b>'.$color->market.'</b>: '.$color->value
                    )->join('<br>')))
                    ->placeholder('NULL')
                    ->label('Цвет для маркетплейса'),
                TextColumn::make('groups')->verticalAlignment(VerticalAlignment::Start)
                    ->getStateUsing(fn(Designs $design) => !$design->groups->count() ? null : new HtmlString($design->groups->map(fn(Groups $group) =>
                        '<b>'.self::variants['groups'][$group->point].'</b>: '.$group->value
                    )->join('<br>')))
                    ->placeholder('NULL')
                    ->label('Группы для дизайна')
            ])
            ->filters([
                SelectFilter::make('is_block')->default(null)
                    ->options([1 => 'Y', 0 => 'N'])
                    ->query(fn(Builder $query, $data) => !strlen($data['value']) ? null : call_user_func(fn($b) => $b ? $query->where('is_block', 'Y') : $query->whereNull('is_block'), $data['value']*1))
                    ->label('Блокировка')
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('block')->action(fn(Collection $records) => call_user_func($toggle['block'], $records, true))->label('Заблокировать'),
                    BulkAction::make('unblock')->action(fn(Collection $records) => call_user_func($toggle['block'], $records))->label('Разблокировать'),
                ])->label('Сменить блокировку')
            ])
            ->actions([
                EditAction::make()
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\Management\Design\Pages\ListDesigns::route('/')
        ];
    }
}
