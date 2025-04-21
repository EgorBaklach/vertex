<?php namespace App\Models\Dev\OZON;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class AmountsAbstract extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $fillable = [
        'sku',
        'sid',
        'type',
        'amount'
    ];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Prices::class, 'sku', 'sku');
    }

    abstract  public function stock(): BelongsTo;
}
