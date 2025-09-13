<?php namespace App\Models\Dev\OZON;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $pid
 * @property int $id
 * @property string $last_request
 * @property string|null $active
 * @property string $value
 * @property string|null $info
 * @property string|null $picture
 * @property-read Properties $property
 * @method static Builder<static>|PV newModelQuery()
 * @method static Builder<static>|PV newQuery()
 * @method static Builder<static>|PV query()
 * @method static Builder<static>|PV whereActive($value)
 * @method static Builder<static>|PV whereId($value)
 * @method static Builder<static>|PV whereInfo($value)
 * @method static Builder<static>|PV whereLastRequest($value)
 * @method static Builder<static>|PV wherePicture($value)
 * @method static Builder<static>|PV wherePid($value)
 * @method static Builder<static>|PV whereValue($value)
 * @mixin Builder
 */
class PV extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ozon_pv';
    protected $fillable = [
        'last_request',
        'active',
        'value',
        'info',
        'picture'
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Properties::class, 'did', 'did');
    }
}
