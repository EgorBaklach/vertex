<?php

namespace App\Filament\Resources\WbOrderResource\Pages;

use App\Filament\Resources\WbOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWbOrders extends ListRecords
{
    protected static string $resource = WbOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
