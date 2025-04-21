<?php namespace App\Models\Dev\YM;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $offer_id
 * @property string $type
 * @property string $updatedAt
 * @property int $value
 * @property float|null $discountBase
 * @property-read Products $product
 * @method static Builder<static>|Prices newModelQuery()
 * @method static Builder<static>|Prices newQuery()
 * @method static Builder<static>|Prices query()
 * @method static Builder<static>|Prices whereDiscountBase($value)
 * @method static Builder<static>|Prices whereOfferId($value)
 * @method static Builder<static>|Prices whereType($value)
 * @method static Builder<static>|Prices whereUpdatedAt($value)
 * @method static Builder<static>|Prices whereValue($value)
 * @mixin Builder
 */
class Prices extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_prices';
    protected $fillable = [
        'offer_id',
        'type',
        'updatedAt',
        'value',
        'discountBase'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'offer_id', 'offerId');
    }
}
