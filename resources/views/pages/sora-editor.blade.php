<x-filament-panels::page>
    <x-filament::section>
        <x-filament-panels::form wire:submit="save" @keydown.window="['1','2','3','4','s'].includes($event.key) && $wire.toggle($event.key)">
            {{ $this->form }}
        </x-filament-panels::form>
    </x-filament::section>
</x-filament-panels::page>
