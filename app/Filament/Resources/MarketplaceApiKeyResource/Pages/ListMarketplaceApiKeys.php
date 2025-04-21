<?php

namespace App\Filament\Resources\MarketplaceApiKeyResource\Pages;

use App\Filament\Resources\MarketplaceApiKeyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMarketplaceApiKeys extends ListRecords
{
    protected static string $resource = MarketplaceApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
