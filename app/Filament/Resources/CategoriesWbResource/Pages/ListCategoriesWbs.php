<?php

namespace App\Filament\Resources\CategoriesWbResource\Pages;

use App\Filament\Resources\CategoriesWbResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategoriesWbs extends ListRecords
{
    protected static string $resource = CategoriesWbResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
