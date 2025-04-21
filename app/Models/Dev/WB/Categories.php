<?php namespace App\Models\Dev\WB;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


/**
 * @property int $id
 * @property string $last_request
 * @property string|null $active
 * @property int $childs
 * @property string $name
 * @property int $parentId
 * @property int $cnt
 * @property-read Categories|null $parent
 * @property-read Collection<int, Properties> $properties
 * @property-read int|null $properties_count
 * @method static Builder<static>|Categories newModelQuery()
 * @method static Builder<static>|Categories newQuery()
 * @method static Builder<static>|Categories query()
 * @method static Builder<static>|Categories whereActive($value)
 * @method static Builder<static>|Categories whereChilds($value)
 * @method static Builder<static>|Categories whereId($value)
 * @method static Builder<static>|Categories whereLastRequest($value)
 * @method static Builder<static>|Categories whereName($value)
 * @method static Builder<static>|Categories whereParentId($value)
 * @mixin Builder
 */
class Categories extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'wb_categories';
    protected $fillable = [
        'last_request',
        'active',
        'childs',
        'name',
        'parentId',
        'cnt'
    ];

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Properties::class, 'wb_cp', 'cid', 'pid'); //->with('values')
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parentId', 'id')->where('active', 'Y');
    }
}
