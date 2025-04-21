<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WbOrderStatus extends Model
{
    protected $table = 'wb_order_statuses';
    protected $fillable = ['code', 'description', 'status_type'];
    public static function getStatusesByType(string $type)
    {
        return self::where('status_type', $type)->pluck('description', 'code');
    }
}
