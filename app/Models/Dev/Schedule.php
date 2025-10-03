<?php namespace App\Models\Dev;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $active
 * @property string $name
 * @property string $market
 * @property string $operation
 * @property int|null $next_start
 * @property int|null $start
 * @property int $counter
 * @property int $ttl
 * @property string $command
 * @property OperatorInterface $handler
 * @method static Builder<static>|Schedule newModelQuery()
 * @method static Builder<static>|Schedule newQuery()
 * @method static Builder<static>|Schedule query()
 * @method static Builder<static>|Schedule whereActive($value)
 * @method static Builder<static>|Schedule whereId($value)
 * @method static Builder<static>|Schedule whereOperation($value)
 * @method static Builder<static>|Schedule whereNextStart($value)
 * @method static Builder<static>|Schedule whereStart($value)
 * @method static Builder<static>|Schedule whereCounter($value)
 * @method static Builder<static>|Schedule whereCommand($value)
 * @mixin Builder
 */
class Schedule extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'schedule';
    protected $fillable = [
        'active',
        'market',
        'operation',
        'next_start',
        'start',
        'ttl',
        'counter',
        'command'
    ];

    public function name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->market.'_'.$this->operation
        );
    }

    public function handler(): Attribute
    {
        return Attribute::make(
            get: fn() => new class(...explode(' ', $this->command)) implements OperatorInterface
            {
                public array $params;

                public function __construct(public string $command, ...$params)
                {
                    $this->params = $params;
                }
            }
        );
    }
}
