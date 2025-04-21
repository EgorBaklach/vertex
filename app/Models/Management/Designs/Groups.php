<?php namespace App\Models\Management\Designs;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Designs $design
 * @property string $point
 * @property string $value
 * @method static Builder<static>|Groups newModelQuery()
 * @method static Builder<static>|Groups newQuery()
 * @method static Builder<static>|Groups query()
 * @method static Builder<static>|Groups whereDesign($value)
 * @method static Builder<static>|Groups wherePoint($value)
 * @method static Builder<static>|Groups whereValue($value)
 * @mixin Builder
 */
class Groups extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'management';
    protected $table = 'groups';
    protected $fillable = [
        'design',
        'point',
        'value'
    ];

    public function design(): BelongsTo
    {
        return $this->belongsTo(Designs::class, 'design', 'article');
    }
}
