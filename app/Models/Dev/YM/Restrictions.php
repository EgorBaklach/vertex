<?php namespace App\Models\Dev\YM;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $mppvid
 * @property int $sppvid
 * @property-read PV $master_value
 * @property-read PV $slave_value
 * @method static Builder<static>|Restrictions newModelQuery()
 * @method static Builder<static>|Restrictions newQuery()
 * @method static Builder<static>|Restrictions query()
 * @method static Builder<static>|Restrictions whereMppvid($value)
 * @method static Builder<static>|Restrictions whereSppvid($value)
 * @mixin Builder
 */
class Restrictions extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_restrictions';
    protected $fillable = [
        'mppvid',
        'sppvid'
    ];

    public function master_value(): BelongsTo
    {
        return $this->belongsTo(PV::class, 'mppvid', 'id');
    }

    public function slave_value(): BelongsTo
    {
        return $this->belongsTo(PV::class, 'sppvid', 'id');
    }
}
