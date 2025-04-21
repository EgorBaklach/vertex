<?php namespace App\Models\Dev\YM;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $last_request
 * @property string|null $active
 * @property string $name
 * @property int|null $pid
 * @property int $level
 * @property int $childs
 * @property int $cnt
 * @property-read Collection<int, CP> $cp
 * @property-read int|null $cp_count
 * @property-read Categories|null $parent
 * @method static Builder<static>|Categories newModelQuery()
 * @method static Builder<static>|Categories newQuery()
 * @method static Builder<static>|Categories query()
 * @method static Builder<static>|Categories whereActive($value)
 * @method static Builder<static>|Categories whereChilds($value)
 * @method static Builder<static>|Categories whereId($value)
 * @method static Builder<static>|Categories whereLastRequest($value)
 * @method static Builder<static>|Categories whereLevel($value)
 * @method static Builder<static>|Categories whereName($value)
 * @method static Builder<static>|Categories wherePid($value)
 * @mixin Builder
 */
class Categories extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_categories';
    protected $fillable = [
        'last_request',
        'active',
        'name',
        'parent_id',
        'lavels',
        'childs',
        'cnt'
    ];

    public function cp(): HasMany
    {
        return $this->hasMany(CP::class, 'cid', 'id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'pid', 'id')->where('active', 'Y');
    }
}
