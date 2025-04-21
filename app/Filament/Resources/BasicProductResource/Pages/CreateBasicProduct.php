<?php

namespace App\Filament\Resources\BasicProductResource\Pages;

use App\Filament\Resources\BasicProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBasicProduct extends CreateRecord
{
    protected static string $resource = BasicProductResource::class;
}
