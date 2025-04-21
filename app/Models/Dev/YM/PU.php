<?php namespace App\Models\Dev\YM;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $pid
 * @property int $uid
 * @property string|null $def
 * @property-read Properties $property
 * @property-read Units $unit
 * @method static Builder<static>|PU newModelQuery()
 * @method static Builder<static>|PU newQuery()
 * @method static Builder<static>|PU query()
 * @method static Builder<static>|PU whereDef($value)
 * @method static Builder<static>|PU wherePid($value)
 * @method static Builder<static>|PU whereUid($value)
 * @mixin Builder
 */
class PU extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_pu';
    protected $fillable = [
        'pid',
        'uid'
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Properties::class, 'pid', 'id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Units::class, 'uid', 'id');
    }
}
