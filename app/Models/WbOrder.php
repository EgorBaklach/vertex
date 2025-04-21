<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WbOrder extends Model
{
    protected $table = 'wb_orders';

    protected $fillable = [
        'id', 'token_id', 'supply_id', 'order_uid', 'article', 'rid',
        'created_at', 'warehouse_id', 'nm_id', 'chrt_id', 'price',
        'converted_price', 'currency_code', 'converted_currency_code',
        'cargo_type', 'is_zero_order', 'supplier_status', 'wb_status',
        'address', 'scan_price', 'comment', 'delivery_type', 'color_code',
        'offices', 'skus'
    ];

    protected $casts = [
        'offices' => 'array',
        'skus' => 'array',
    ];

    public function supplierStatusDescription()
    {
        return $this->hasOne(WbOrderStatus::class, 'code', 'supplier_status')
            ->where('status_type', 'supplierStatus');
    }

    public function wbStatusDescription()
    {
        return $this->hasOne(WbOrderStatus::class, 'code', 'wb_status')
            ->where('status_type', 'wbStatus');
    }

    public function token()
    {
        return $this->belongsTo(MarketplaceApiKey::class, 'token_id', 'id');
    }
}

