<?php namespace App\Filament\Resources\Dev;

use App\Filament\Clusters\Dev;
use App\Models\Dev\Schedule;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $cluster = Dev::class;

    protected static ?string $modelLabel = 'Расписание операций';
    protected static ?string $pluralModelLabel = 'Расписание операций';

    protected static ?string $navigationLabel = 'Расписание операций';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'GENERAL';

    public static function getTitle(): string
    {
        return self::class;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('active')->nullable()->default(null)->options(['Y' => 'Y'])->placeholder('N')->label('Вкл'),
            Select::make('market')->selectablePlaceholder(false)->default('WB')->options(['WB' => 'WB', 'OZON' => 'OZON', 'YM' => 'YM'])->label('Маркетплейс'),
            TextInput::make('operation')->required()->label('Наименование операции'),
            TextInput::make('next_start')->nullable()->default(null)->numeric()->label('Время след запуска'),
            TextInput::make('ttl')->required()->default(300)->numeric()->label('Время жизни процесса'),
            TextInput::make('command')->required()->label('Оператор запуска')
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->groups([Group::make('market')->titlePrefixedWithLabel(false)])
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('active')->placeholder('NULL')->label('Вкл'),
                TextColumn::make('operation')->label('Наименование операции'),
                TextColumn::make('next_start')->formatStateUsing(fn($state) => date('Y-m-d H:i:s', $state))->placeholder('NULL')->label('Время след запуска'),
                TextColumn::make('ttl')->label('Время жизни процесса'),
                TextColumn::make('counter')->label('Счетчик запусков'),
                TextColumn::make('command')->label('Оператор запуска')
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\ForceDeleteAction::make(),
                Actions\RestoreAction::make(),
            ])
            ->filters([
                SelectFilter::make('market')->options(['WB' => 'WB', 'OZON' => 'OZON', 'YM' => 'YM'])->attribute('market')->label('Маркетплейс')
            ])
            ->paginationPageOptions([50,100,'all'])
            ->defaultPaginationPageOption(50)
            ->defaultGroup('market');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchedule::route('/')
        ];
    }
}
