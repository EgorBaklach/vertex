<?php namespace App\Models\Dev\WB;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property string|null $active
 * @property int $cid
 * @property int $pid
 * @property-read Categories $category
 * @property-read Properties $property
 * @method static Builder<static>|CP newModelQuery()
 * @method static Builder<static>|CP newQuery()
 * @method static Builder<static>|CP query()
 * @method static Builder<static>|CP whereActive($value)
 * @method static Builder<static>|CP whereCid($value)
 * @method static Builder<static>|CP wherePid($value)
 * @mixin QueryBuilder
 */
class CP extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'wb_cp';
    protected $fillable = [
        'cid',
        'pid'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categories::class, 'cid', 'id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Properties::class, 'pid', 'id');
    }
}
