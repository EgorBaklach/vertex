<?php namespace App\Models\Dev\OZON;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id Номер задания на формирование документов.
 * @property string $last_request
 * @property string|null $active
 * @property int|null $attribute_complex_id Идентификатор комплексного атрибута.
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property string|null $is_collection Признак набора значений.
 * @property string|null $is_required Признак обязательной характеристики.
 * @property string|null $is_aspect Признак аспектного атрибута.
 * @property int $max_value_count
 * @property int|null $group_id Идентификатор группы характеристик.
 * @property string|null $group_name Название группы характеристик.
 * @property int|null $did Идентификатор справочника.
 * @property string|null $category_dependent Признак, что значения зависят от категории.
 * @property-read Collection<int, Categories> $categories
 * @property-read int|null $categories_count
 * @property-read Collection<int, Types> $types
 * @property-read int|null $types_count
 * @property-read Collection<int, CTP> $ctps
 * @property-read int|null $ctps_count
 * @property-read Collection<int, PV> $pvs
 * @property-read int|null $pvs_count
 * @method static Builder<static>|Properties newModelQuery()
 * @method static Builder<static>|Properties newQuery()
 * @method static Builder<static>|Properties query()
 * @method static Builder<static>|Properties whereActive($value)
 * @method static Builder<static>|Properties whereAttributeComplexId($value)
 * @method static Builder<static>|Properties whereCategoryDependent($value)
 * @method static Builder<static>|Properties whereDescription($value)
 * @method static Builder<static>|Properties whereDid($value)
 * @method static Builder<static>|Properties whereGroupId($value)
 * @method static Builder<static>|Properties whereGroupName($value)
 * @method static Builder<static>|Properties whereId($value)
 * @method static Builder<static>|Properties whereIsAspect($value)
 * @method static Builder<static>|Properties whereIsCollection($value)
 * @method static Builder<static>|Properties whereIsRequired($value)
 * @method static Builder<static>|Properties whereLastRequest($value)
 * @method static Builder<static>|Properties whereMaxValueCount($value)
 * @method static Builder<static>|Properties whereName($value)
 * @method static Builder<static>|Properties whereType($value)
 * @mixin Builder
 */
class Properties extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ozon_properties';
    protected $fillable = [
        'last_request',
        'active',
        'attribute_complex_id',
        'name',
        'description',
        'type',
        'is_collection',
        'is_required',
        'is_aspect',
        'max_value_count',
        'group_id',
        'group_name',
        'did',
        'category_dependent'
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Categories::class, 'ozon_ctp', 'pid', 'cid');
    }

    public function types(): BelongsToMany
    {
        return $this->belongsToMany(Types::class, 'ozon_ctp', 'pid', 'tid');
    }

    public function ctps(): HasMany
    {
        return $this->hasMany(CTP::class, 'pid', 'id');
    }

    public function pvs(): HasMany
    {
        return $this->hasMany(PV::class, 'did', 'did');
    }
}
