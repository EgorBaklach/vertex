<?php

namespace App\Console\Commands;

use App\Models\ApiLog;
use App\Models\MarketplaceApiKey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class GetWBProducts extends Command
{
    protected $signature = 'fetch:wb-products';
    protected $description = 'Fetch products from Wildberries API and update database';

    public function handle(): int
    {
        if (!$apiKey = MarketplaceApiKey::where('marketplace', 'Wildberries')->first())
        {
            $this->logError('API key not found for Wildberries.'); return SymfonyCommand::FAILURE;
        }

        try
        {
            $response = Http::withHeaders(['Authorization' => $apiKey->wb_api_key, 'Content-type' => 'application/json'])->timeout(120)
                ->post('https://content-api.wildberries.ru/content/v2/get/cards/list', [
                    'settings' => [
                        'cursor' => [
                            'limit' => 1
                        ]
                    ]
                ]);

            if ($response->failed())
            {
                $this->logError('Failed to fetch data from Wildberries API. Response: '.$response->body()); return SymfonyCommand::FAILURE;
            }

            Log::info($response->body()); $this->logSuccess('Products imported successfully.'); return SymfonyCommand::SUCCESS;
        }
        catch (\Exception $e)
        {
            $this->logError('Error while fetching data from Wildberries API: ' . $e->getMessage()); return SymfonyCommand::FAILURE;
        }
    }

    protected function logError(string $message): void
    {
        Log::error($message);
        ApiLog::create([
            'marketplace' => 'Wildberries',
            'message' => $message,
            'success' => false,
        ]);
    }

    protected function logSuccess(string $message): void
    {
        Log::info($message);
        ApiLog::create([
            'marketplace' => 'Wildberries',
            'message' => $message,
            'success' => true,
        ]);
    }
}
