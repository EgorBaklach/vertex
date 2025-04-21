<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiLogsResource\Pages;
use App\Models\ApiLog;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;

class ApiLogsResource extends Resource
{
    protected static ?string $model = ApiLog::class;

    protected static ?string $navigationGroup = 'WB';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $label = 'Логи API';
    protected static ?string $pluralLabel = 'Логи API';

    public static function form(Forms\Form $form): Forms\Form // Corrected
    {
        return $form->schema([]);
    }

    public static function table(Tables\Table $table): Tables\Table // Corrected
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('marketplace')->label('Маркетплейс')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('message')->label('Сообщение')->searchable(),
                Tables\Columns\BadgeColumn::make('success')
                    ->label('Статус')
                    ->formatStateUsing(fn($state) => $state ? 'Успешно' : 'Ошибка')
                    ->color(fn($state) => $state ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('created_at')->label('Дата')->sortable(),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiLogs::route('/'),
        ];
    }
}
