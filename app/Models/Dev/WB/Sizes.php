<?php namespace App\Models\Dev\WB;

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
 * @property int $chrtID
 * @property int $nmID
 * @property int $tid
 * @property string $last_request
 * @property string|null $active
 * @property string|null $techSize
 * @property string|null $wbSize
 * @property string $sku
 * @property-read Collection<int, FBOAmounts> $fbo_amounts
 * @property-read int|null $fbo_amounts_count
 * @property-read Collection<int, FBSAmounts> $fbs_amounts
 * @property-read int|null $fbs_amounts_count
 * @property-read Prices|null $price
 * @property-read Products $product
 * @property-read MarketplaceApiKey $token
 * @method static Builder<static>|Sizes newModelQuery()
 * @method static Builder<static>|Sizes newQuery()
 * @method static Builder<static>|Sizes query()
 * @method static Builder<static>|Sizes whereActive($value)
 * @method static Builder<static>|Sizes whereChrtID($value)
 * @method static Builder<static>|Sizes whereLastRequest($value)
 * @method static Builder<static>|Sizes whereNmID($value)
 * @method static Builder<static>|Sizes whereSku($value)
 * @method static Builder<static>|Sizes whereTechSize($value)
 * @method static Builder<static>|Sizes whereTid($value)
 * @method static Builder<static>|Sizes whereWbSize($value)
 * @mixin Builder
 */
class Sizes extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'wb_sizes';
    protected $fillable = [
        'chrtID',
        'nmID',
        'tid',
        'last_request',
        'active',
        'techSize',
        'wbSize',
        'sku'
    ];

    public function price(): HasOne
    {
        return $this->hasOne(Prices::class, 'sizeID', 'chrtID');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'nmID', 'nmID');
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(MarketplaceApiKey::class, 'tid', 'id');
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
