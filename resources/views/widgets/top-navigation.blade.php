<x-filament-widgets::widget>
    <x-filament::section>
        <div class="bg-white shadow p-4">
            <div class="flex justify-between items-center">
                <div class="flex space-x-6">
                    <a href="{{ route('filament.admin.resources.users.index') }}" class="text-blue-600 hover:underline">
                        <x-heroicon-o-users class="w-5 h-5 inline"/>
                        Пользователи
                    </a>
                    <a href="{{ route('filament.admin.resources.shield.roles.index') }}"
                       class="text-blue-600 hover:underline">
                        <x-heroicon-o-key class="w-5 h-5 inline"/>
                        Роли
                    </a>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
