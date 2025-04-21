<?php namespace App\Models\Management;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $pattern
 * @property string $description
 * @property string $relation
 * @method static Builder<static>|PatternAbstract newModelQuery()
 * @method static Builder<static>|PatternAbstract newQuery()
 * @method static Builder<static>|PatternAbstract query()
 * @method static Builder<static>|PatternAbstract whereId($value)
 * @method static Builder<static>|PatternAbstract wherePattern($value)
 * @method static Builder<static>|PatternAbstract whereDescription($value)
 * @method static Builder<static>|PatternAbstract whereRelation($value)
 * @mixin Builder
 */
abstract class PatternAbstract extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'management';
    protected $table = 'patterns';
    protected $fillable = [
        'pattern',
        'description',
        'relation'
    ];
}
