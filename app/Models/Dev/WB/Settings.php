<?php namespace App\Models\Dev\WB;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property int $id
 * @property string $last_request
 * @property string $variable
 * @property string|null $value
 * @method static Builder<static>|Settings newModelQuery()
 * @method static Builder<static>|Settings newQuery()
 * @method static Builder<static>|Settings query()
 * @method static Builder<static>|Settings whereId($value)
 * @method static Builder<static>|Settings whereLastRequest($value)
 * @method static Builder<static>|Settings whereValue($value)
 * @method static Builder<static>|Settings whereVariable($value)
 * @mixin QueryBuilder
 */
class Settings extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'wb_settings';
    protected $fillable = [
        'last_request',
        'variable',
        'value'
    ];
}
