<?php namespace App\Models\Dev\WB;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $last_request
 * @property string|null $active
 * @property string $name
 * @property int $total
 * @property-read Collection<int, FBOAmounts> $skus
 * @property-read int|null $skus_count
 * @method static Builder<static>|FBOStocks newModelQuery()
 * @method static Builder<static>|FBOStocks newQuery()
 * @method static Builder<static>|FBOStocks query()
 * @method static Builder<static>|FBOStocks whereActive($value)
 * @method static Builder<static>|FBOStocks whereId($value)
 * @method static Builder<static>|FBOStocks whereLastRequest($value)
 * @method static Builder<static>|FBOStocks whereName($value)
 * @method static Builder<static>|FBOStocks whereTotal($value)
 * @mixin Builder
 */
class FBOStocks extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'wb_fbo_stocks';
    protected $fillable = [
        'last_request',
        'active',
        'name',
        'total'
    ];

    public function skus(): HasMany
    {
        return $this->hasMany(FBOAmounts::class, 'sid', 'id');
    }
}
