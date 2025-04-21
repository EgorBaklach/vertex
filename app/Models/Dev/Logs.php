<?php namespace App\Models\Dev;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property string $value
 * @method static Builder<static>|Logs newModelQuery()
 * @method static Builder<static>|Logs newQuery()
 * @method static Builder<static>|Logs query()
 * @method static Builder<static>|Logs whereValue($value)
 * @mixin QueryBuilder
 */
class Logs extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'logs';
    protected $fillable = [
        'entity',
        'value'
    ];
}
