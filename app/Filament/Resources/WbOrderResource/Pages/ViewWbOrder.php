<?php

namespace App\Filament\Resources\WbOrderResource\Pages;

use App\Filament\Resources\WbOrderResource;
use Filament\Resources\Pages\ViewRecord;

class ViewWbOrder extends ViewRecord
{
    protected static string $resource = WbOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
