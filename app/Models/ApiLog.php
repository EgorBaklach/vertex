<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use HasFactory;

    protected $table = 'api_logs'; // Указываем имя таблицы

    protected $fillable = [
        'marketplace',
        'message',
        'success',
    ];
}
