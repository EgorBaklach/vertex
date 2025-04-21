<?php namespace App\Filament\Resources\CategoriesWbResource\Pages;

use App\Filament\Resources\CategoriesWbResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategoriesWb extends EditRecord
{
    protected static string $resource = CategoriesWbResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
