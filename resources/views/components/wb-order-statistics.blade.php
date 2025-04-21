<div class="p-4 bg-white shadow rounded-md mb-4">
    <!-- Общая статистика -->
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center text-lg bg-gray-100 p-4 rounded-md">
        <div class="flex items-center justify-center space-x-3">
            <i class="fas fa-briefcase text-blue-600 text-xl"></i>
            <span>Всего заказов в работе:</span>
            <strong class="text-blue-800 text-xl">{{ $overallStatistics->in_work ?? 0 }}</strong>
        </div>
        <div class="flex items-center justify-center space-x-3">
            <i class="fas fa-tools text-green-600 text-xl"></i>
            <span>Всего заказов на сборке:</span>
            <strong class="text-green-800 text-xl">{{ $overallStatistics->on_assembly ?? 0 }}</strong>
        </div>
        <div class="flex items-center justify-center space-x-3">
            <i class="fas fa-plus-circle text-orange-600 text-xl"></i>
            <span>Всего новых заказов:</span>
            <strong class="text-orange-800 text-xl">{{ $overallStatistics->new_orders ?? 0 }}</strong>
        </div>
    </div>
<br>
    <!-- Кнопка загрузки заказов -->
    <div class="flex justify-between items-center mb-4">
        <h4 class="text-lg font-bold">Статистика заказов</h4>
        <form method="POST" action="{{ route('filament.resources.wb-orders.loadOrders') }}">
            @csrf
            <button
                type="submit"
                style="background-color: #3b82f6; color: white; padding: 10px 20px; font-weight: bold; border-radius: 5px; box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);"
                onclick="return confirm('Вы действительно хотите загрузить заказы из Wildberries?')"
            >
                <i class="heroicon-o-cloud-arrow-down" style="margin-right: 8px;"></i> Загрузить заказы
            </button>
        </form>
    </div>

    <!-- Статистика по токенам -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach ($statistics->groupBy('token_name') as $tokenName => $statuses)
            <div
                class="filter-cell border p-3 rounded-md shadow-sm cursor-pointer"
                data-token-id="{{ $statuses->first()->token_id }}"
            >
                <strong>{{ $tokenName }}</strong>
                <ul class="mt-2 text-sm">
                    @foreach ($statuses as $status)
                        <li>
                            {{ $status->supplier_status ?? 'Не указан' }}:
                            <strong>{{ $status->total_orders }}</strong>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</div>
