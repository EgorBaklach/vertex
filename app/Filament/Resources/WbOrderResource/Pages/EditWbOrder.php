<?php

namespace App\Filament\Resources\WbOrderResource\Pages;

use App\Filament\Resources\WbOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWbOrder extends EditRecord
{
    protected static string $resource = WbOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
