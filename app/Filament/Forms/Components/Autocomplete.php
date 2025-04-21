<?php namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\TextInput;

class Autocomplete extends TextInput
{
    protected array $options = [];

    protected string $view = 'forms.components.autocomplete';

    protected function setUp(): void
    {
        parent::setUp(); $this->autocomplete('off');
    }

    public function setOptions(array | Closure $options): self
    {
        $this->options = $this->evaluate($options); return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
