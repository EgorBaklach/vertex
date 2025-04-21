<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CutScan extends Model
{
    use HasFactory;

    protected $table = 'cut_scan';

protected $fillable = [
    'barcode',
    'order_number',
    'user_id',
    'windows_user',
    'scanned_at',
];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
