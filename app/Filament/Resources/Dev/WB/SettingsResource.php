<?php namespace App\Filament\Resources\Dev\WB;

use App\Filament\Clusters\Dev;
use App\Models\Dev\WB\Settings;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions;
use Filament\Tables\Table;

class SettingsResource extends Resource
{
    protected static ?string $model = Settings::class;

    protected static ?string $cluster = Dev::class;

    protected static ?string $modelLabel = 'Настройка';
    protected static ?string $pluralModelLabel = 'Настройки';

    protected static ?string $navigationLabel = 'Настройки';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'WB';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('variable')->required()->label('Переменная'),
            TextInput::make('value')->nullable()->default(null)->label('Значение')
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)->paginationPageOptions([10, 25, 50, 'all'])->columns([
                TextColumn::make('id')->label('id'),
                TextColumn::make('last_request')->label('Дата обработки'),
                TextColumn::make('variable')->label('Переменная'),
                TextColumn::make('value')->placeholder('NULL')->label('Значение')
            ])->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\ForceDeleteAction::make(),
                Actions\RestoreAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSettings::route('/')];
    }
}
