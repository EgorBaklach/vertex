<?php namespace App\Models\Dev\WB;

use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property int $nmID
 * @property int $imtID
 * @property string $nmUUID
 * @property string|null $active
 * @property int $tid
 * @property string $createdAt
 * @property string $updatedAt
 * @property int $cid
 * @property string $vendorCode
 * @property string $brand
 * @property string $title
 * @property string|null $description
 * @property string $dimensions
 * @property-read Categories $category
 * @property-read MarketplaceApiKey $token
 * @property-read Collection<int, Sizes> sizes
 * @property-read Collection<int, PPV> ppvs
 * @property-read Collection<int, Files> files
 * @method static Builder<static>|Products newModelQuery()
 * @method static Builder<static>|Products newQuery()
 * @method static Builder<static>|Products query()
 * @method static Builder<static>|Products whereActive($value)
 * @method static Builder<static>|Products whereBrand($value)
 * @method static Builder<static>|Products whereCid($value)
 * @method static Builder<static>|Products whereCreatedAt($value)
 * @method static Builder<static>|Products whereDescription($value)
 * @method static Builder<static>|Products whereDimensions($value)
 * @method static Builder<static>|Products whereImtID($value)
 * @method static Builder<static>|Products whereNmID($value)
 * @method static Builder<static>|Products whereNmUUID($value)
 * @method static Builder<static>|Products whereTid($value)
 * @method static Builder<static>|Products whereTitle($value)
 * @method static Builder<static>|Products whereUpdatedAt($value)
 * @method static Builder<static>|Products whereVendorCode($value)
 * @mixin QueryBuilder
 */
class Products extends Model
{
    use HasFactory, CustomQueries;

    protected $primaryKey = 'nmID';
    protected $keyType = 'bigint';

    public $incrementing = false;
    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'wb_products';
    protected $fillable = [
        'nmID',
        'imtID',
        'nmUUID',
        'last_request',
        'active',
        'tid',
        'inTrash',
        'createdAt',
        'updatedAt',
        'cid',
        'vendorCode',
        'brand',
        'title',
        'description',
        'dimensions'
    ];

    public function token(): BelongsTo
    {
        return $this->belongsTo(MarketplaceApiKey::class, 'tid', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categories::class, 'cid', 'id');
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(Sizes::class, 'nmID', 'nmID')->with('price');
    }

    public function ppvs(): hasMany
    {
        return $this->hasMany(PPV::class, 'nmID', 'nmID')->with('property')->with('pv');
    }

    public function files(): hasMany
    {
        return $this->hasMany(Files::class, 'nmID', 'nmID');
    }

    /*public function property_value(): HasManyThrough
    {
        return $this->hasManyThrough(Properties::class, PPV::class, 'nmID', 'id', 'nmID', 'pid')->with('ppvs:pdiv,value');
    }*/
}
