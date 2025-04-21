<?php namespace App\Filament\Resources\Dev\Pages;

use App\Filament\Resources\Dev\ProxyResource;
use App\Models\Dev\Proxies;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListProxyResource extends ListRecords
{
    protected static string $resource = ProxyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('counter:clear')
                ->requiresConfirmation()
                ->action(function (array $data)
                {
                    Proxies::query()->update(($data['state'] * 1 ? ['process' => 0] : []) + ['success' => 0, 'abort' => 0, 'last_request' => NULL]);
                    Notification::make()->title('Счетчики успешно очищены')->success()->send();
                })
                ->modalHeading('Подтвердите действие')
                ->modalDescription('Если необходимо очистить полностью, укажите это ниже')
                ->form([Toggle::make('state')->label('Очистить полностью?')->default(false)])
                ->label('Очистить счетчики'),
            Action::make('reload')
                ->action(fn () => $this->callHook('refresh'))
                ->label('Обновить страницу')
        ];
    }
}
