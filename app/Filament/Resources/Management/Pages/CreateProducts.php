<?php namespace App\Filament\Resources\Management\Pages;

use App\Filament\Resources\Management\Pages\Traits\CustomMutator;
use App\Filament\Resources\Management\ProductsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProducts extends CreateRecord
{
    use CustomMutator;

    protected static string $resource = ProductsResource::class;
}
