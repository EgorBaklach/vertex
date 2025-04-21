<?php

namespace App\Console\Commands;

use App\Models\MarketplaceApiKey;
use App\Models\ApiLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FetchWbOrders extends Command
{
    protected $signature = 'fetch:wb-orders';
    protected $description = 'Загрузка заказов и статусов из Wildberries API';

    public function handle()
    {
        try {
            // Получаем все активные ключи для Wildberries
            $apiKeys = MarketplaceApiKey::where('marketplace', 'Wildberries')->get();

            if ($apiKeys->isEmpty()) {
                $this->logError('API ключи для Wildberries не найдены.');
                return Command::FAILURE;
            }

            foreach ($apiKeys as $apiKey) {
                $this->logSuccess("Используется API ключ: {$apiKey->name}");

                $lastRequest = $apiKey->last_orders_request
                    ? (new \DateTime($apiKey->last_orders_request))->modify('-5 day')->format('U')
                    : strtotime('2025-01-01 00:00:01');

                $next = 0;
                $success = true;
                $orderIds = [];

                do {
                    $response = Http::withHeaders([
                        'Authorization' => $apiKey->wb_api_key,
                    ])->get("https://marketplace-api.wildberries.ru/api/v3/orders", [
                        'limit' => 1000,
                        'next' => $next,
                        'dateFrom' => $lastRequest,
                    ]);

                    if ($response->ok()) {
                        $data = $response->json();
                        $this->logSuccess("Получены заказы: " . count($data['orders']));
                        $this->storeOrders($data['orders'], $apiKey->id);

                        // Собираем все ID заказов для загрузки статусов
                        $orderIds = array_merge($orderIds, array_column($data['orders'], 'id'));
                        $next = $data['next'];
                    } else {
                        $success = false;
                        $this->logError("Ошибка API для токена {$apiKey->id}: {$response->body()}");
                        break;
                    }
                } while ($next !== 0);

                // Загружаем статусы заказов
                if ($success && !empty($orderIds)) {
                    $this->fetchOrderStatuses($orderIds, $apiKey->wb_api_key);
                }

                // Обновляем время последнего запроса только при успешной обработке
                if ($success) {
                    DB::table('marketplace_api_keys')
                        ->where('id', $apiKey->id)
                        ->update(['last_orders_request' => now()]);
                }
            }

            $this->logSuccess('Команда выполнена успешно.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->logError('Ошибка выполнения команды: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function storeOrders(array $orders, int $apiKeyId)
    {
        foreach ($orders as $order) {
            DB::table('wb_orders')->updateOrInsert(
                ['id' => $order['id']],
                [
                    'token_id' => $apiKeyId,
                    'supply_id' => $order['supplyId'] ?? null,
                    'order_uid' => $order['orderUid'] ?? null,
                    'article' => $order['article'] ?? null,
                    'rid' => $order['rid'] ?? null,
                    'created_at' => isset($order['createdAt']) 
                        ? \Carbon\Carbon::parse($order['createdAt'])->format('Y-m-d H:i:s') 
                        : null,
                    'warehouse_id' => $order['warehouseId'] ?? null,
                    'nm_id' => $order['nmId'] ?? null,
                    'chrt_id' => $order['chrtId'] ?? null,
                    'price' => $order['price'] ?? null,
                    'converted_price' => $order['convertedPrice'] ?? null,
                    'currency_code' => $order['currencyCode'] ?? null,
                    'converted_currency_code' => $order['convertedCurrencyCode'] ?? null,
                    'cargo_type' => $order['cargoType'] ?? null,
                    'is_zero_order' => $order['isZeroOrder'] ?? null,
                    'address' => $order['address'] ?? null,
                    'scan_price' => $order['scanPrice'] ?? null,
                    'comment' => $order['comment'] ?? null,
                    'delivery_type' => $order['deliveryType'] ?? null,
                    'color_code' => $order['colorCode'] ?? null,
                    'offices' => json_encode($order['offices'] ?? []),
                    'skus' => json_encode($order['skus'] ?? []),
                ]
            );
        }
    }

    private function fetchOrderStatuses(array $orderIds, string $apiKey)
    {
        $chunks = array_chunk($orderIds, 1000);

        foreach ($chunks as $chunk) {
            $response = Http::withHeaders([
                'Authorization' => $apiKey,
            ])->post("https://marketplace-api.wildberries.ru/api/v3/orders/status", [
                'orders' => $chunk,
            ]);

            if ($response->ok()) {
                $statuses = $response->json()['orders'];
                foreach ($statuses as $status) {
                    DB::table('wb_orders')->where('id', $status['id'])->update([
                        'supplier_status' => $status['supplierStatus'] ?? null,
                        'wb_status' => $status['wbStatus'] ?? null,
                    ]);
                }
                $this->logSuccess("Обновлены статусы для " . count($statuses) . " заказов.");
            } else {
                $this->logError("Ошибка при загрузке статусов заказов: {$response->body()}");
            }
        }
    }

    protected function logError(string $message): void
    {
        ApiLog::create([
            'marketplace' => 'Wildberries',
            'message' => $message,
            'success' => false,
        ]);
    }

    protected function logSuccess(string $message): void
    {
        ApiLog::create([
            'marketplace' => 'Wildberries',
            'message' => $message,
            'success' => true,
        ]);
    }
}
