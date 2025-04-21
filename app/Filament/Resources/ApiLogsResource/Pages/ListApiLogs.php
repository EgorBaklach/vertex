<?php

namespace App\Filament\Resources\ApiLogsResource\Pages;

use App\Filament\Resources\ApiLogsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApiLogs extends ListRecords
{
    protected static string $resource = ApiLogsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
