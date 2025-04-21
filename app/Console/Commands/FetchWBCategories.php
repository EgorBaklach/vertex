<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MarketplaceApiKey;
use App\Models\CategoryWb;
use App\Models\CategoryUpdateHistory;
use App\Models\ApiLog;
use Illuminate\Support\Facades\Http;

class FetchWBCategories extends Command
{
    protected $signature = 'fetch:wb-categories';
    protected $description = 'Fetch categories from Wildberries API and update database';

public function handle()
{
    $apiKey = MarketplaceApiKey::where('marketplace', 'Wildberries')->first();

    if (!$apiKey) {
        $this->logError('API key not found for Wildberries.');
        return Command::FAILURE;
    }

    try {
        // Увеличиваем таймаут запроса
        $response = Http::withHeaders([
            'Authorization' => $apiKey->wb_api_key,
        ])
            ->timeout(120) // Устанавливаем время ожидания в 120 секунд
            ->get($apiKey->base_api_url . '/api/v1/tariffs/commission');

        if ($response->failed()) {
            $this->logError('Failed to fetch data from Wildberries API. Response: ' . $response->body());
            return Command::FAILURE;
        }

        $categories = $response->json('report');
        if (!$categories) {
            $this->logError('No categories found in the response from Wildberries API.');
            return Command::FAILURE;
        }

        foreach ($categories as $category) {
            $this->updateOrCreateCategory($category);
        }

        $this->logSuccess('Categories updated successfully.');
        return Command::SUCCESS;
    } catch (\Exception $e) {
        $this->logError('Error while fetching data from Wildberries API: ' . $e->getMessage());
        return Command::FAILURE;
    }
}


private function updateOrCreateCategory(array $categoryData)
{
    $existingCategory = CategoryWb::where('parent_id', $categoryData['parentID'])
        ->where('subject_id', $categoryData['subjectID'])
        ->first();

    $newData = [
        'parent_id' => $categoryData['parentID'],
        'parent_name' => $categoryData['parentName'],
        'subject_id' => $categoryData['subjectID'],
        'subject_name' => $categoryData['subjectName'],
        'kgvp_marketplace' => $categoryData['kgvpMarketplace'],
        'kgvp_supplier' => $categoryData['kgvpSupplier'],
        'kgvp_supplier_express' => $categoryData['kgvpSupplierExpress'],
        'paid_storage_kgvp' => $categoryData['paidStorageKgvp'],
    ];

    if ($existingCategory) {
        if ($this->hasChanges($existingCategory, $newData)) {
            // Преобразуем данные в JSON
            CategoryUpdateHistory::create([
                'category_id' => $existingCategory->id,
                'old_data' => json_encode($existingCategory->toArray(), JSON_UNESCAPED_UNICODE),
                'new_data' => json_encode($newData, JSON_UNESCAPED_UNICODE),
            ]);
            $existingCategory->update($newData);
        }
    } else {
        CategoryWb::create($newData);
    }
}


    private function hasChanges($existingCategory, $newData)
    {
        foreach ($newData as $key => $value) {
            if ($existingCategory->$key != $value) {
                return true;
            }
        }
        return false;
    }

protected function logError(string $message): void
{
    \Log::error($message);
    ApiLog::create([
        'marketplace' => 'Wildberries',
        'message' => $message,
        'success' => false,
    ]);
}

protected function logSuccess(string $message): void
{
    \Log::info($message);
    ApiLog::create([
        'marketplace' => 'Wildberries',
        'message' => $message,
        'success' => true,
    ]);
}

}