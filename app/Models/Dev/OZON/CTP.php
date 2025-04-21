<?php namespace App\Models\Dev\OZON;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $cid
 * @property int $tid
 * @property int $pid
 * @property-read Categories $category
 * @property-read Properties $property
 * @property-read Types $type
 * @method static Builder<static>|CTP newModelQuery()
 * @method static Builder<static>|CTP newQuery()
 * @method static Builder<static>|CTP query()
 * @method static Builder<static>|CTP whereCid($value)
 * @method static Builder<static>|CTP wherePid($value)
 * @method static Builder<static>|CTP whereTid($value)
 * @mixin Builder
 */
class CTP extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ozon_ctp';
    protected $fillable = [
        'cid',
        'tid',
        'pid',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categories::class, 'cid', 'id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Types::class, 'tid', 'id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Properties::class, 'pid', 'id');
    }
}
