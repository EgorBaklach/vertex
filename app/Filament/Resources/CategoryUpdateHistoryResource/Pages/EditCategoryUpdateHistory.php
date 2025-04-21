<?php

namespace App\Filament\Resources\CategoryUpdateHistoryResource\Pages;

use App\Filament\Resources\CategoryUpdateHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategoryUpdateHistory extends EditRecord
{
    protected static string $resource = CategoryUpdateHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
