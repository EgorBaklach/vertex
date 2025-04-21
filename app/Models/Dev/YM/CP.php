<?php namespace App\Models\Dev\YM;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $cid
 * @property int $pid
 * @property string $name
 * @property string|null $description
 * @property int|null $constMaxLength Макс длина текста
 * @property float|null $constMinValue Мин число
 * @property float|null $constMaxValue Макс число
 * @property-read Categories $category
 * @property-read PU|null $du
 * @property-read Properties $property
 * @method static Builder<static>|CP newModelQuery()
 * @method static Builder<static>|CP newQuery()
 * @method static Builder<static>|CP query()
 * @method static Builder<static>|CP whereCid($value)
 * @method static Builder<static>|CP whereConstMaxLength($value)
 * @method static Builder<static>|CP whereConstMaxValue($value)
 * @method static Builder<static>|CP whereConstMinValue($value)
 * @method static Builder<static>|CP whereDescription($value)
 * @method static Builder<static>|CP whereName($value)
 * @method static Builder<static>|CP wherePid($value)
 * @mixin Builder
 */
class CP extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_cp';
    protected $fillable = [
        'cid',
        'pid',
        'name',
        'description',
        'constMaxLength',
        'constMinValue',
        'constMaxValue'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categories::class, 'cid', 'id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Properties::class, 'pid', 'id');
    }

    public function du(): HasOne
    {
        return $this->hasOne(PU::class, 'pid', 'pid')->where('def', '=', 'Y')->with('unit');
    }
}
