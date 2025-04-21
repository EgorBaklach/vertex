<?php namespace App\Models\Dev\YM;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $offer_id
 * @property string $last_request
 * @property string|null $active
 * @property int $pid
 * @property string $value
 * @property string|null $description
 * @property-read Properties $property
 * @method static Builder<static>|PV newModelQuery()
 * @method static Builder<static>|PV newQuery()
 * @method static Builder<static>|PV query()
 * @method static Builder<static>|PV whereActive($value)
 * @method static Builder<static>|PV whereDescription($value)
 * @method static Builder<static>|PV whereId($value)
 * @method static Builder<static>|PV whereLastRequest($value)
 * @method static Builder<static>|PV wherePid($value)
 * @method static Builder<static>|PV whereValue($value)
 * @mixin Builder
 */
class PV extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_pv';
    protected $fillable = [
        'offer_id',
        'last_request',
        'active',
        'pid',
        'value',
        'description'
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Properties::class, 'pid', 'id');
    }
}
