<?php namespace App\Models\Dev\OZON;

use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $pid
 * @property string $created_at
 * @property string $updated_at
 * @property string $last_request
 * @property string|null $active
 * @property int $token_id
 * @property int $sku
 * @property float $marketing_price Цена на товар с учётом всех акций. Это значение будет указано на витрине Ozon
 * @property float|null $min_price Минимальная цена товара после применения акций
 * @property float $old_price Цена до учёта скидок. На карточке товара отображается зачёркнутой
 * @property float $price Цена товара с учётом скидок — это значение показывается на карточке товара
 * @property float|null $vat Ставка НДС для товара
 * @property string $price_index Ценновые индексы товара
 * @property string|null $visible_by_price
 * @property string|null $visible_by_stock
 * @property string $source
 * @property string $shipment_type
 * @property string|null $fbo
 * @property string|null $fbs
 * @property-read Collection<int, FBOAmounts> $fbo_amounts
 * @property-read int|null $fbo_amounts_count
 * @property-read Collection<int, FBSAmounts> $fbs_amounts
 * @property-read int|null $fbs_amounts_count
 * @property-read Products $product
 * @property-read MarketplaceApiKey $token
 * @method static Builder<static>|Prices newModelQuery()
 * @method static Builder<static>|Prices newQuery()
 * @method static Builder<static>|Prices query()
 * @method static Builder<static>|Prices whereActive($value)
 * @method static Builder<static>|Prices whereCreatedAt($value)
 * @method static Builder<static>|Prices whereFbo($value)
 * @method static Builder<static>|Prices whereFbs($value)
 * @method static Builder<static>|Prices whereLastRequest($value)
 * @method static Builder<static>|Prices whereMarketingPrice($value)
 * @method static Builder<static>|Prices whereMinPrice($value)
 * @method static Builder<static>|Prices whereOldPrice($value)
 * @method static Builder<static>|Prices wherePid($value)
 * @method static Builder<static>|Prices wherePrice($value)
 * @method static Builder<static>|Prices whereShipmentType($value)
 * @method static Builder<static>|Prices whereSku($value)
 * @method static Builder<static>|Prices whereSource($value)
 * @method static Builder<static>|Prices whereTokenId($value)
 * @method static Builder<static>|Prices whereUpdatedAt($value)
 * @method static Builder<static>|Prices whereVat($value)
 * @method static Builder<static>|Prices whereVisibleByPrice($value)
 * @method static Builder<static>|Prices whereVisibleByStock($value)
 * @mixin Builder
 */
class Prices extends Model
{
    use HasFactory, CustomQueries;

    protected $primaryKey = 'sku';
    protected $keyType = 'bigint';

    public $incrementing = false;
    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ozon_prices';
    protected $fillable = [
        'pid',
        'created_at',
        'updated_at',
        'last_request',
        'active',
        'token_id',
        'sku',
        'marketing_price',
        'min_price',
        'old_price',
        'price',
        'vat',
        'price_index',
        'visible_by_price',
        'visible_by_stock',
        'source',
        'shipment_type',
        'fbo',
        'fbs'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'pid', 'id');
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(MarketplaceApiKey::class, 'token_id', 'id');
    }

    public function fbo_amounts(): HasMany
    {
        return $this->hasMany(FBOAmounts::class, 'sku', 'sku')->with('stock');
    }

    public function fbs_amounts(): HasMany
    {
        return $this->hasMany(FBSAmounts::class, 'sku', 'sku')->with('stock');
    }
}
