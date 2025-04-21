<?php namespace App\Models\Dev\WB;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property int $nmID
 * @property int $pid
 * @property int|null $pvid
 * @property string|null $value
 * @property-read Products $product
 * @property-read Properties $property
 * @property-read PV|null pv
 * @method static Builder<static>|PPV newModelQuery()
 * @method static Builder<static>|PPV newQuery()
 * @method static Builder<static>|PPV query()
 * @method static Builder<static>|PPV whereNmID($value)
 * @method static Builder<static>|PPV wherePid($value)
 * @method static Builder<static>|PPV wherePvid($value)
 * @method static Builder<static>|PPV whereValue($value)
 * @mixin QueryBuilder
 */
class PPV extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'wb_ppv';
    protected $fillable = [
        'nmID',
        'pid',
        'pvid',
        'value'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'nmID', 'nmID');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Properties::class, 'pid', 'id');
    }

    public function pv(): BelongsTo
    {
        return $this->belongsTo(PV::class, 'pvid', 'id');
    }
}
