<?php namespace App\Models\Dev\OZON;

use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $last_request
 * @property string|null $active
 * @property string $offer_id Идентификатор товара в системе продавца — артикул
 * @property string|null $barcode Штрихкод
 * @property int $token_id
 * @property int|null $cid
 * @property int|null $tid
 * @property string $name
 * @property string $dimensions
 * @property string $dimension_unit
 * @property int $weight
 * @property string $weight_unit
 * @property int|null $model_id Идентификатор модели
 * @property int|null $model_count Количество объединённых товаров модели
 * @property string|null $color_image Маркетинговый цвет
 * @property string|null $archived Товар в архиве
 * @property string|null $has_fbo_stocks
 * @property string|null $has_fbs_stocks
 * @property string|null $is_discounted Уценённый товар
 * @property string|null $quants Эконом товар
 * @property-read Categories|null $category
 * @property-read Collection<int, Commissions> $commissions
 * @property-read int|null $commissions_count
 * @property-read Collection<int, Errors> $errors
 * @property-read int|null $errors_count
 * @property-read Collection<int, Files> $files
 * @property-read int|null $files_count
 * @property-read Collection<int, Indexes> $indexes
 * @property-read int|null $indexes_count
 * @property-read Collection<int, PPV> $ppvs
 * @property-read int|null $ppvs_count
 * @property-read Collection<int, Prices> $skus
 * @property-read int|null $skus_count
 * @property-read Statuses|null $statuses
 * @property-read MarketplaceApiKey $token
 * @property-read Types|null $type
 * @method static Builder<static>|Products newModelQuery()
 * @method static Builder<static>|Products newQuery()
 * @method static Builder<static>|Products query()
 * @method static Builder<static>|Products whereActive($value)
 * @method static Builder<static>|Products whereArchived($value)
 * @method static Builder<static>|Products whereBarcode($value)
 * @method static Builder<static>|Products whereCid($value)
 * @method static Builder<static>|Products whereColorImage($value)
 * @method static Builder<static>|Products whereDimensionUnit($value)
 * @method static Builder<static>|Products whereDimensions($value)
 * @method static Builder<static>|Products whereHasFboStocks($value)
 * @method static Builder<static>|Products whereHasFbsStocks($value)
 * @method static Builder<static>|Products whereId($value)
 * @method static Builder<static>|Products whereIsDiscounted($value)
 * @method static Builder<static>|Products whereLastRequest($value)
 * @method static Builder<static>|Products whereModelCount($value)
 * @method static Builder<static>|Products whereModelId($value)
 * @method static Builder<static>|Products whereName($value)
 * @method static Builder<static>|Products whereOfferId($value)
 * @method static Builder<static>|Products whereQuants($value)
 * @method static Builder<static>|Products whereTid($value)
 * @method static Builder<static>|Products whereTokenId($value)
 * @method static Builder<static>|Products whereWeight($value)
 * @method static Builder<static>|Products whereWeightUnit($value)
 * @mixin Builder
 */
class Products extends Model
{
    use HasFactory, CustomQueries;

    protected $primaryKey = 'id';
    protected $keyType = 'bigint';

    public $incrementing = false;
    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ozon_products';
    protected $fillable = [
        'last_request',
        'active',
        'offer_id',
        'barcode',
        'token_id',
        'cid',
        'tid',
        'name',
        'dimensions',
        'dimension_unit',
        'weight',
        'weight_unit',
        'model_id',
        'model_count',
        'has_fbo_stocks',
        'has_fbs_stocks',
        'is_dicounted',
        'quants'
    ];

    public function token(): BelongsTo
    {
        return $this->belongsTo(MarketplaceApiKey::class, 'token_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categories::class, 'cid', 'id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Types::class, 'tid', 'id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(Files::class, 'pid', 'id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commissions::class, 'pid', 'id');
    }

    public function indexes(): HasMany
    {
        return $this->hasMany(Indexes::class, 'pid', 'id');
    }

    public function errors(): HasMany
    {
        return $this->hasMany(Errors::class, 'product_id', 'id');
    }

    public function statuses(): HasOne
    {
        return $this->hasOne(Statuses::class, 'pid', 'id');
    }

    public function skus(): HasMany
    {
        return $this->hasMany(Prices::class, 'pid', 'id');
    }

    public function ppvs(): hasMany
    {
        return $this->hasMany(PPV::class, 'product_id', 'id')->with('property')->with('pv');
    }
}
