<?php namespace App\Models\Dev\WB;

use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

/**
 * @property int $sizeID
 * @property int $nmID
 * @property int $tid
 * @property string $last_request
 * @property string|null $active
 * @property float $price
 * @property float|null $wbPrice
 * @property float $discountedPrice
 * @property float $clubDiscountedPrice
 * @property float $discount
 * @property float $clubDiscount
 * @property-read Sizes $product
 * @property-read Collection<int, Sizes> $sizes
 * @property-read int|null $sizes_count
 * @property-read MarketplaceApiKey $token
 * @method static Builder<static>|Prices newModelQuery()
 * @method static Builder<static>|Prices newQuery()
 * @method static Builder<static>|Prices query()
 * @method static Builder<static>|Prices whereActive($value)
 * @method static Builder<static>|Prices whereClubDiscount($value)
 * @method static Builder<static>|Prices whereClubDiscountedPrice($value)
 * @method static Builder<static>|Prices whereDiscount($value)
 * @method static Builder<static>|Prices whereDiscountedPrice($value)
 * @method static Builder<static>|Prices whereLastRequest($value)
 * @method static Builder<static>|Prices whereNmID($value)
 * @method static Builder<static>|Prices wherePrice($value)
 * @method static Builder<static>|Prices whereSizeID($value)
 * @method static Builder<static>|Prices whereTid($value)
 * @method static Builder<static>|Prices whereWbPrice($value)
 * @mixin Builder
 */
class Prices extends Model
{
    use HasFactory, CustomQueries;

    protected $primaryKey = 'sizeID';

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'wb_prices';
    protected $fillable = [
        'sizeID',
        'nmID',
        'tid',
        'last_request',
        'active',
        'price',
        'discountedPrice',
        'clubDiscountedPrice',
        'discount',
        'clubDiscount'
    ];

    public function sizes(): HasOneOrMany
    {
        return $this->hasMany(Sizes::class, 'chrtID', 'sizeID');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'nmID', 'nmID');
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(MarketplaceApiKey::class, 'tid', 'id');
    }
}
