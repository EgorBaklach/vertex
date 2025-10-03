<?php namespace App\Filament\Resources\Dev\Pages;

use App\Filament\Resources\Dev\ScheduleResource;
use App\Models\Dev\Schedule;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSchedule extends ListRecords
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('counter:clear')
                ->requiresConfirmation()
                ->action(function ()
                {
                    Schedule::query()->update(['start' => null, 'counter' => 0]); Notification::make()->title('Счетчики успешно очищены')->success()->send();
                })
                ->modalHeading('Подтвердите действие')
                ->label('Очистить счетчики'),
            Action::make('reload')
                ->action(fn () => $this->callHook('refresh'))
                ->label('Обновить страницу')
        ];
    }
}
