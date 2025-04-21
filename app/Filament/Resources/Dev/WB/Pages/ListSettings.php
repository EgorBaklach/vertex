<?php namespace App\Filament\Resources\Dev\WB\Pages;

use App\Filament\Resources\Dev\WB\SettingsResource;
use App\Models\Dev\WB\Settings;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('table:truncate')
                ->requiresConfirmation()
                ->action(fn() => Settings::query()->truncate())
                ->modalHeading('Подтвердите действие')
                ->label('Очистить таблицу'),
            Action::make('reload')
                ->action(fn () => $this->callHook('refresh'))
                ->label('Обновить страницу')
        ];
    }
}
