<?php namespace App\Filament\Forms\Components;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Arr;

class ImageUpload extends FileUpload
{
    protected string $view = 'forms.components.image-upload';

    protected function setUp(): void
    {
        parent::setUp(); $this->multiple(false)->fetchFileInformation(false)->previewable()->disabled()->image();

        $this->getUploadedFileUsing(fn($file): ?array => ['name' => basename($file), 'size' => 0, 'type' => null, 'url' => $file]);
    }

    public function getUrl(): string
    {
        return Arr::first($this->getState());
    }
}
