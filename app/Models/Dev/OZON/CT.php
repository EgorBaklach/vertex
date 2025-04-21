<?php namespace App\Models\Dev\OZON;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $cid
 * @property int $tid
 * @property-read Categories $category
 * @property-read Types $type
 * @method static Builder<static>|CT newModelQuery()
 * @method static Builder<static>|CT newQuery()
 * @method static Builder<static>|CT query()
 * @method static Builder<static>|CT whereCid($value)
 * @method static Builder<static>|CT whereTid($value)
 * @mixin Builder
 */
class CT extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ozon_ct';
    protected $fillable = [
        'cid',
        'tid'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categories::class, 'cid', 'id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Types::class, 'tid', 'id');
    }
}
