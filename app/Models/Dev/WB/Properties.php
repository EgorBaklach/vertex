<?php namespace App\Models\Dev\WB;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property int $id
 * @property string $last_request
 * @property string|null $active
 * @property string $name
 * @property string|null $required
 * @property string|null $unit
 * @property int $count
 * @property string|null $popular
 * @property int $type
 * @property-read Collection<int, Categories> $categories
 * @property-read int|null $categories_count
 * @property-read Collection<int, PV> $values
 * @property-read int|null $values_count
 * @method static Builder<static>|Properties newModelQuery()
 * @method static Builder<static>|Properties newQuery()
 * @method static Builder<static>|Properties query()
 * @method static Builder<static>|Properties whereActive($value)
 * @method static Builder<static>|Properties whereCount($value)
 * @method static Builder<static>|Properties whereId($value)
 * @method static Builder<static>|Properties whereLastRequest($value)
 * @method static Builder<static>|Properties whereName($value)
 * @method static Builder<static>|Properties wherePopular($value)
 * @method static Builder<static>|Properties whereRequired($value)
 * @method static Builder<static>|Properties whereType($value)
 * @method static Builder<static>|Properties whereUnit($value)
 * @mixin QueryBuilder
 */
class Properties extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'wb_properties';
    protected $fillable = [
        'last_request',
        'active',
        'name',
        'required',
        'unit',
        'count',
        'popular',
        'type'
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Categories::class, 'wb_cp', 'pid', 'cid');
    }

    public function values(): HasMany
    {
        return $this->hasMany(PV::class, 'pid', 'id');
    }
}
