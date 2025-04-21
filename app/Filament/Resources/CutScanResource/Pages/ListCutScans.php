<?php

namespace App\Filament\Resources\CutScanResource\Pages;

use App\Filament\Resources\CutScanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCutScans extends ListRecords
{
    protected static string $resource = CutScanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
