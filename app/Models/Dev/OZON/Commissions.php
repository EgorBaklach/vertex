<?php namespace App\Models\Dev\OZON;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $pid
 * @property string $last_request
 * @property string|null $active
 * @property float $delivery_amount Стоимость доставки
 * @property float $percent Процент комиссии
 * @property float $return_amount Стоимость возврата
 * @property string $sale_schema Схема продажи
 * @property float $value Сумма комиссии
 * @property-read Products $product
 * @method static Builder<static>|Commissions newModelQuery()
 * @method static Builder<static>|Commissions newQuery()
 * @method static Builder<static>|Commissions query()
 * @method static Builder<static>|Commissions whereActive($value)
 * @method static Builder<static>|Commissions whereDeliveryAmount($value)
 * @method static Builder<static>|Commissions whereLastRequest($value)
 * @method static Builder<static>|Commissions wherePercent($value)
 * @method static Builder<static>|Commissions wherePid($value)
 * @method static Builder<static>|Commissions whereReturnAmount($value)
 * @method static Builder<static>|Commissions whereSaleSchema($value)
 * @method static Builder<static>|Commissions whereValue($value)
 * @mixin Builder
 */
class Commissions extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ozon_commissions';
    protected $fillable = [
        'pid',
        'last_request',
        'active',
        'delivery_amount',
        'percent',
        'return_amount',
        'sale_schema',
        'value'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'pid', 'id');
    }
}
