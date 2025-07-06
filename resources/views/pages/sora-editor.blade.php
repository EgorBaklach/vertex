<x-filament-panels::page>
    <x-filament::section>
        @if($this->data)
            <x-filament-panels::form
                @keydown.window="
                !$wire.data.selectGen && $wire.data.abort !== 'Y' && ![$wire.last_position, null].includes($wire.data.position) &&
                ['1','2','3','4','b','s',' ','x'].includes($event.key) &&
                $wire.toggle($event.key) &&
                $event.preventDefault()"
                x-init="document.querySelectorAll('.js-zoomer .fi-fo-file-upload').forEach(zoomer)"
                wire:key="{{ $this->data['number'] }}"
                wire:submit="save">
                {{ $this->form }}
            </x-filament-panels::form>
        @else
            <div wire:poll.visible.5s="reload" class="p-4 text-center text-gray-500">Все записи обработаны!</div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
