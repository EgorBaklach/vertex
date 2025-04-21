<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryWb extends Model
{
    use HasFactory;

    protected $table = 'categories_wb';

    protected $fillable = [
        'parent_id',
        'parent_name',
        'subject_id',
        'subject_name',
        'kgvp_marketplace',
        'kgvp_supplier',
        'kgvp_supplier_express',
        'paid_storage_kgvp',
    ];
}
