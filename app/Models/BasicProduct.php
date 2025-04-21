<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasicProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'article',
        'product_name',
        'price_with_discount',
        'price_without_discount',
		'minimum_price', // Новое поле
        'package_length_mm',
        'package_height_mm',
        'package_width_mm',
        'package_weight_g',
        'currency',
        'vat',
        'description',
        'is_size_based',
        'size_chart',
    ];

    public function category()
    {
        return $this->belongsTo(CategoryWb::class, 'category_id');
    }
}
