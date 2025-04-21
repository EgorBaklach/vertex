<?php namespace App\Filament\Resources\Management\Design\Pages;

use App\Filament\Resources\Management\Design\DesignsResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDesigns extends ListRecords
{
    protected static string $resource = DesignsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('reload')
                ->action(fn () => $this->callHook('refresh'))
                ->label('Обновить страницу')
        ];
    }
}
