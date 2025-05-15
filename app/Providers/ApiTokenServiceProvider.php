<?php namespace App\Providers;

use App\Models\ApiAccessToken;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class ApiTokenServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(ApiAccessToken::class);
    }
}