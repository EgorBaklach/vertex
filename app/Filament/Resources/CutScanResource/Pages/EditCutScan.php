<?php

namespace App\Filament\Resources\CutScanResource\Pages;

use App\Filament\Resources\CutScanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCutScan extends EditRecord
{
    protected static string $resource = CutScanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
