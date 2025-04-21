<?php namespace App\Models\Dev\OZON;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $last_request
 * @property string|null $active
 * @property string|null $disabled
 * @property string $name
 * @property int $parent_id
 * @property int $level
 * @property int $childs
 * @property-read Collection<int, Properties> $properties
 * @property-read int|null $properties_count
 * @property-read Collection<int, Types> $types
 * @property-read int|null $types_count
 * @method static Builder<static>|Categories newModelQuery()
 * @method static Builder<static>|Categories newQuery()
 * @method static Builder<static>|Categories query()
 * @method static Builder<static>|Categories whereActive($value)
 * @method static Builder<static>|Categories whereChilds($value)
 * @method static Builder<static>|Categories whereDisabled($value)
 * @method static Builder<static>|Categories whereId($value)
 * @method static Builder<static>|Categories whereLastRequest($value)
 * @method static Builder<static>|Categories whereLevel($value)
 * @method static Builder<static>|Categories whereName($value)
 * @method static Builder<static>|Categories whereParentId($value)
 * @mixin Builder
 */
class Categories extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ozon_categories';
    protected $fillable = [
        'last_request',
        'active',
        'disabled',
        'name',
        'childs',
        'parent_id'
    ];

    public function types(): BelongsToMany
    {
        return $this->belongsToMany(Types::class, 'ozon_ct', 'cid', 'tid');
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Properties::class, 'ozon_ctp', 'cid', 'pid');
    }
}
