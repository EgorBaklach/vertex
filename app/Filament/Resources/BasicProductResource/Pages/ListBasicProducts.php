<?php

namespace App\Filament\Resources\BasicProductResource\Pages;

use App\Filament\Resources\BasicProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBasicProducts extends ListRecords
{
    protected static string $resource = BasicProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
