<?php namespace App\Filament\Resources\Management\Pages;

use App\Filament\Resources\Management\Pages\Traits\CustomMutator;
use App\Filament\Resources\Management\ProductsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProducts extends EditRecord
{
    use CustomMutator;

    protected static string $resource = ProductsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
