<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceApiKey extends Model
{
    use HasFactory;

    /**
     * Разрешённые для массового заполнения поля.
     */
    protected $fillable = [
        'marketplace',
        'name',
        'base_api_url',
        'ozon_api_key',
        'ozon_client_id',
        'wb_api_key',
        'ym_api_key',
        'ym_campaign_id',
        'ym_business_id',
        'mm_api_key',
        'mm_cabinet_id',
        'last_orders_request',
    ];
}
