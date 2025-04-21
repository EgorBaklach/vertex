<?php namespace App\Models\Dev\YM;

use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int|null $sku_id
 * @property string $last_request
 * @property string|null $active
 * @property string|null $archived
 * @property int $tid
 * @property int $cid
 * @property int|null $modelId
 * @property string $offerId
 * @property string|null $modelName
 * @property string|null $skuName
 * @property string|null $name
 * @property string|null $vendor
 * @property string|null $vendorCode
 * @property string|null $barcodes
 * @property string|null $description
 * @property string|null $manufacturerCountries
 * @property string|null $weightDimensions
 * @property string|null $tags
 * @property int|null $boxCount Количество грузовых мест
 * @property string $cardStatus Статус карточки товара
 * @property string|null $type Особый тип товара
 * @property string|null $downloadable
 * @property string|null $adult
 * @property-read Categories $category
 * @property-read Collection<int, CommodityCodes> $commodity_codes
 * @property-read int|null $commodity_codes_count
 * @property-read Collection<int, Docs> $docs
 * @property-read int|null $docs_count
 * @property-read Collection<int, FBSAmounts> $fbs_amounts
 * @property-read int|null $fbs_amounts_count
 * @property-read Collection<int, Notices> $notices
 * @property-read int|null $notices_count
 * @property-read Collection<int, PPV> $ppvs
 * @property-read int|null $ppvs_count
 * @property-read Collection<int, Prices> $prices
 * @property-read int|null $prices_count
 * @property-read Rating|null $rating
 * @property-read Recommendations|null $recommendations
 * @property-read Collection<int, SellingPrograms> $selling_programs
 * @property-read int|null $selling_programs_count
 * @property-read Collection<int, Times> $times
 * @property-read int|null $times_count
 * @property-read MarketplaceApiKey $token
 * @method static Builder<static>|Products newModelQuery()
 * @method static Builder<static>|Products newQuery()
 * @method static Builder<static>|Products query()
 * @method static Builder<static>|Products whereActive($value)
 * @method static Builder<static>|Products whereAdult($value)
 * @method static Builder<static>|Products whereArchived($value)
 * @method static Builder<static>|Products whereBarcodes($value)
 * @method static Builder<static>|Products whereBoxCount($value)
 * @method static Builder<static>|Products whereCardStatus($value)
 * @method static Builder<static>|Products whereCid($value)
 * @method static Builder<static>|Products whereDescription($value)
 * @method static Builder<static>|Products whereDownloadable($value)
 * @method static Builder<static>|Products whereLastRequest($value)
 * @method static Builder<static>|Products whereManufacturerCountries($value)
 * @method static Builder<static>|Products whereModelId($value)
 * @method static Builder<static>|Products whereModelName($value)
 * @method static Builder<static>|Products whereName($value)
 * @method static Builder<static>|Products whereOfferId($value)
 * @method static Builder<static>|Products whereSkuId($value)
 * @method static Builder<static>|Products whereSkuName($value)
 * @method static Builder<static>|Products whereTags($value)
 * @method static Builder<static>|Products whereTid($value)
 * @method static Builder<static>|Products whereType($value)
 * @method static Builder<static>|Products whereVendor($value)
 * @method static Builder<static>|Products whereVendorCode($value)
 * @method static Builder<static>|Products whereWeightDimensions($value)
 * @mixin Builder
 */
class Products extends Model
{
    use HasFactory, CustomQueries;

    protected $primaryKey = 'offerId';
    protected $keyType = 'string';

    public $incrementing = false;
    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_products';
    protected $fillable = [
        'sku_id',
        'last_request',
        'active',
        'archived',
        'tid',
        'cid',
        'modelId',
        'offerId',
        'modelName',
        'skuName',
        'name',
        'vendor',
        'vendorCode',
        'barcodes',
        'description',
        'manufacturerCountries',
        'weightDimensions',
        'tags',
        'boxCount',
        'cardStatus',
        'type',
        'downloadable',
        'adult'
    ];

    public function token(): BelongsTo
    {
        return $this->belongsTo(MarketplaceApiKey::class, 'tid', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categories::class, 'cid', 'id');
    }

    public function docs(): HasMany
    {
        return $this->hasMany(Docs::class, 'offer_id', 'offerId');
    }

    public function commodity_codes(): BelongsToMany
    {
        return $this->belongsToMany(CommodityCodes::class, 'ym_pcc', 'offer_id', 'commodity_code');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Prices::class, 'offer_id', 'offerId');
    }

    public function selling_programs(): HasMany
    {
        return $this->hasMany(SellingPrograms::class, 'offer_id', 'offerId');
    }

    public function times(): HasMany
    {
        return $this->hasMany(Times::class, 'offer_id', 'offerId');
    }

    public function ppvs(): hasMany
    {
        return $this->hasMany(PPV::class, 'offer_id', 'offerId')->with('pv')->with('unit');
    }

    public function notices(): HasMany
    {
        return $this->hasMany(Notices::class, 'offer_id', 'offerId');
    }

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class, 'offer_id', 'offerId');
    }

    public function recommendations(): HasOne
    {
        return $this->hasOne(Recommendations::class, 'product_offer_id', 'offerId');
    }

    public function fbs_amounts(): HasMany
    {
        return $this->hasMany(FBSAmounts::class, 'offer_id', 'offerId')->orderBy('sid')->with('stock');
    }
}
