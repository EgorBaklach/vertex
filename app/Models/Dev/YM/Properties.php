<?php namespace App\Models\Dev\YM;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $last_request
 * @property string|null $active
 * @property string $type
 * @property string|null $required
 * @property string|null $filtering Используется ли характеристика в фильтре
 * @property string|null $distinctive Является ли характеристика особенностью варианта
 * @property string|null $multivalue Можно ли передать сразу несколько значений
 * @property string|null $allowCustomValues Можно ли передавать собственное значение
 * @property string|null $hasValues
 * @property string|null $hasValueRestrictions Есть значения ограничивающие запись
 * @property-read Collection<int, CP> $cp
 * @property-read int|null $cp_count
 * @property-read Collection<int, PU> $pu
 * @property-read int|null $pu_count
 * @method static Builder<static>|Properties newModelQuery()
 * @method static Builder<static>|Properties newQuery()
 * @method static Builder<static>|Properties query()
 * @method static Builder<static>|Properties whereActive($value)
 * @method static Builder<static>|Properties whereAllowCustomValues($value)
 * @method static Builder<static>|Properties whereDistinctive($value)
 * @method static Builder<static>|Properties whereFiltering($value)
 * @method static Builder<static>|Properties whereHasValueRestrictions($value)
 * @method static Builder<static>|Properties whereHasValues($value)
 * @method static Builder<static>|Properties whereId($value)
 * @method static Builder<static>|Properties whereLastRequest($value)
 * @method static Builder<static>|Properties whereMultivalue($value)
 * @method static Builder<static>|Properties whereRequired($value)
 * @method static Builder<static>|Properties whereType($value)
 * @mixin Builder
 */
class Properties extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_properties';
    protected $fillable = [
        'last_request',
        'active',
        'type',
        'defaultUnitId',
        'filtering',
        'distinctive',
        'multivalue',
        'allowCustomValues',
        'hasValues',
        'hasValueRestrictions'
    ];

    public function cp(): HasMany
    {
        return $this->hasMany(CP::class, 'pid', 'id');
    }

    public function pv(int $count = null): HasMany
    {
        return call_user_func([$this->hasMany(PV::class, 'pid', 'id'), 'limit'], $count);
    }

    public function pu(): HasMany
    {
        return $this->hasMany(PU::class, 'pid', 'id');
    }
}
