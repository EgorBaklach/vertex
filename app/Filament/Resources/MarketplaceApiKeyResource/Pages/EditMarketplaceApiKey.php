<?php

namespace App\Filament\Resources\MarketplaceApiKeyResource\Pages;

use App\Filament\Resources\MarketplaceApiKeyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarketplaceApiKey extends EditRecord
{
    protected static string $resource = MarketplaceApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
