<?php namespace App\Filament\Forms\Components;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ImageUpload extends FileUpload
{
    protected string $view = 'forms.components.image-upload';

    protected function setUp(): void
    {
        parent::setUp(); $this->multiple(false)->previewable()->disabled()->image();
    }

    public function getUrl(): string
    {
        return Storage::disk('public')->url(Arr::first($this->getState()));
    }
}
