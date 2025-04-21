<?php namespace App\Models\Management\Designs;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Designs $design
 * @property string $market
 * @property string $value
 * @method static Builder<static>|Colors newModelQuery()
 * @method static Builder<static>|Colors newQuery()
 * @method static Builder<static>|Colors query()
 * @method static Builder<static>|Colors whereDesign($value)
 * @method static Builder<static>|Colors whereMarket($value)
 * @method static Builder<static>|Colors whereValue($value)
 * @mixin Builder
 */
class Colors extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'management';
    protected $table = 'colors';
    protected $fillable = [
        'design',
        'market',
        'value'
    ];

    public function design(): BelongsTo
    {
        return $this->belongsTo(Designs::class, 'design', 'article');
    }
}
