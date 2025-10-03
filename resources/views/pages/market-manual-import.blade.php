<x-filament-panels::page>
    <x-filament::section>
        <x-filament-panels::form wire:submit="submit">
            {{ $this->form }}
        </x-filament-panels::form>
        <div wire:poll.2s="reload" class="font-medium text-sm pt-6">
            @if(count($this->stats['queue'] ?? []))
                <div class="mb-4">
                    <h2 class="mb-2">ОЧЕРЕДЬ ОПЕРАЦИЙ НА ИМПОРТ</h2>
                    @foreach($this->stats['queue'] as $operation => $markets)
                        <div class="mb-2">{{self::operations[$operation]['label']}}:<br/>@foreach($markets as $market => $tokens){{$market}}: <span class="text-gray-500">{{$tokens}}</span><br/>@endforeach</div>
                    @endforeach
                </div>
            @endif
            @if(count($this->stats['runners'] ?? []))
                <div class="mb-4">
                    <h2 class="mb-2">ОПЕРАЦИИ ЗАПУЩЕННЫЕ В РУЧНОМ РЕЖИМЕ</h2>
                    @foreach($this->stats['runners'] as $market => $operation)
                        <div class="mb-2">{{$market}}: <span class="text-gray-500">{{$operation}}</span></div>
                    @endforeach
                </div>
            @endif
            @if(count($this->stats['processes'] ?? []))
                <h2 class="mb-2">ОПЕРАЦИИ ЗАПУЩЕННЫЕ НА ДАННЫЙ МОМЕНТ</h2>
                @foreach($this->stats['processes'] as $market => $operations)
                    <div>{{$market}}: <span class="text-gray-500">{{implode(', ', Arr::map($operations, fn($v) => $v['label']))}}</span></div>
                @endforeach
            @else
                <div class="text-gray-500">На данный момент импорт остановлен</div>
            @endif
            {{--<div>{{ json_encode($this->stats['in_process'] ?? '-', JSON_UNESCAPED_UNICODE) }}</div>--}}
            {{--<div class="text-2xl font-bold text-primary-600">{{ $this->stats['latest_update'] ?? 'N/A' }}</div>--}}
        </div>
    </x-filament::section>
</x-filament-panels::page>
