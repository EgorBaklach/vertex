<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryUpdateHistory extends Model
{
    use HasFactory;
protected $table = 'category_update_history';
    protected $fillable = [
        'category_id',
        'old_data',
        'new_data',
    ];

    public function category()
    {
        return $this->belongsTo(CategoryWb::class, 'category_id');
    }
}

