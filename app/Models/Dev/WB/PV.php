<?php namespace App\Models\Dev\WB;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property int $id
 * @property string $last_request
 * @property string|null $active
 * @property int $pid
 * @property string $value
 * @property string $bind
 * @property string|null $params
 * @property-read Properties $property
 * @property-read Collection<int, PPV> $values
 * @property-read int|null $values_count
 * @method static Builder<static>|PV newModelQuery()
 * @method static Builder<static>|PV newQuery()
 * @method static Builder<static>|PV query()
 * @method static Builder<static>|PV whereActive($value)
 * @method static Builder<static>|PV whereBind($value)
 * @method static Builder<static>|PV whereId($value)
 * @method static Builder<static>|PV whereLastRequest($value)
 * @method static Builder<static>|PV whereParams($value)
 * @method static Builder<static>|PV wherePid($value)
 * @method static Builder<static>|PV whereValue($value)
 * @mixin QueryBuilder
 */
class PV extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'wb_pv';
    protected $fillable = [
        'last_request',
        'active',
        'pid',
        'value',
        'bind',
        'params'
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Properties::class, 'pid', 'id');
    }

    public function values(): hasMany
    {
        return $this->hasMany(PPV::class, 'pvid', 'id');
    }
}
