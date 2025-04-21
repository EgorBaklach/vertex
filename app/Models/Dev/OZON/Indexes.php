<?php namespace App\Models\Dev\OZON;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $pid
 * @property string $type
 * @property string $last_request
 * @property string|null $active
 * @property float $minimal_price
 * @property float $price_index_value Значение индекса цены
 * @property-read Products $product
 * @method static Builder<static>|Indexes newModelQuery()
 * @method static Builder<static>|Indexes newQuery()
 * @method static Builder<static>|Indexes query()
 * @method static Builder<static>|Indexes whereActive($value)
 * @method static Builder<static>|Indexes whereLastRequest($value)
 * @method static Builder<static>|Indexes whereMinimalPrice($value)
 * @method static Builder<static>|Indexes wherePid($value)
 * @method static Builder<static>|Indexes wherePriceIndexValue($value)
 * @method static Builder<static>|Indexes whereType($value)
 * @mixin Builder
 */
class Indexes extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ozon_indexes';
    protected $fillable = [
        'pid',
        'type',
        'last_request',
        'active',
        'minimal_price',
        'price_index_value'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'pid', 'id');
    }
}
