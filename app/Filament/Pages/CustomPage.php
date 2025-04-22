<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Sora;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;

class CustomPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.custom-page';

    protected static ?string $cluster = Sora::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required(),
            Select::make('selectGen')
                ->options([
                    'option1' => 'Option 1',
                    'option2' => 'Option 2',
                ])
                ->required(),
        ]);
    }
}
