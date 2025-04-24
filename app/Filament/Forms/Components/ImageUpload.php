<?php namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;

class ImageUpload extends FileUpload
{
    protected string $view = 'forms.components.image-upload';

    protected function setUp(): void
    {
        parent::setUp(); $this->multiple(false)->previewable()->disabled()->image();
    }

    public function getUrl(): string
    {
        return env('APP_URL').'storage/'.$this->getState()[0];
    }
}
