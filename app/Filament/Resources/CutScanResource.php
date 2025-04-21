<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CutScanResource\Pages;
use App\Models\CutScan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn; // Импортируем TextColumn

class CutScanResource extends Resource
{
    protected static ?string $model = CutScan::class;

	protected static ?string $navigationGroup = 'Раскрой';
    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';
    protected static ?string $label = 'История сканирования';
    protected static ?string $pluralLabel = 'История сканирования';

public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\TextInput::make('barcode')
            ->label('Штрихкод')
            ->required(),
        Forms\Components\TextInput::make('order_number')
            ->label('Номер заказа')
            ->required(),
        Forms\Components\Select::make('user_id')
            ->relationship('user', 'name')
            ->label('Пользователь'),
        Forms\Components\TextInput::make('windows_user')
            ->label('Пользователь Windows')
            ->disabled(), // Поле доступно только для чтения
        Forms\Components\DateTimePicker::make('scanned_at')
            ->label('Время сканирования')
            ->default(now()),
    ]);
}


    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('barcode')->label('Штрихкод'),
            TextColumn::make('order_number')->label('Номер заказа'),
            TextColumn::make('user.name')->label('Пользователь'),
			TextColumn::make('windows_user')->label('Пользователь Windows'), // Новая колонка
            TextColumn::make('scanned_at')->label('Время сканирования'),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCutScans::route('/'),
            'create' => Pages\CreateCutScan::route('/create'),
            'edit' => Pages\EditCutScan::route('/{record}/edit'),
        ];
    }
}
