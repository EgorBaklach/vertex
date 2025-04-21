<?php namespace App\Models\Dev\YM;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $code
 * @property string $last_request
 * @property string|null $active
 * @property string $type
 * @property-read Collection<int, Products> $products
 * @property-read int|null $products_count
 * @method static Builder<static>|CommodityCodes newModelQuery()
 * @method static Builder<static>|CommodityCodes newQuery()
 * @method static Builder<static>|CommodityCodes query()
 * @method static Builder<static>|CommodityCodes whereActive($value)
 * @method static Builder<static>|CommodityCodes whereCode($value)
 * @method static Builder<static>|CommodityCodes whereLastRequest($value)
 * @method static Builder<static>|CommodityCodes whereType($value)
 * @mixin Builder
 */
class CommodityCodes extends Model
{
    use HasFactory, CustomQueries;

    protected $primaryKey = 'code';
    protected $keyType = 'string';

    public $incrementing = false;
    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_commodity_codes';
    protected $fillable = [
        'code',
        'last_request',
        'active',
        'type'
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Products::class, 'ym_pcc', 'commodity_code', 'pid');
    }
}
