<?php namespace App\Models\Dev;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $type
 * @method static Builder<static>|Agents newModelQuery()
 * @method static Builder<static>|Agents newQuery()
 * @method static Builder<static>|Agents query()
 * @method static Builder<static>|Agents whereId($value)
 * @method static Builder<static>|Agents whereName($value)
 * @method static Builder<static>|Agents whereType($value)
 * @mixin Builder
 */
class Agents extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'agents';
    protected $fillable = [
        'name',
        'type'
    ];
}
