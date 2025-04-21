<?php namespace App\Filament\Resources\PatternResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

abstract class ListAbstract extends ListRecords
{
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
