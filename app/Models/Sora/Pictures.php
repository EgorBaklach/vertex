<?php namespace App\Models\Sora;

use App\Models\Management\Designs\Designs;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $priority
 * @property int|null $position
 * @property string|null $abort
 * @property string|null $date_generate
 * @property string|null $date_update
 * @property int|null $uid
 * @property string|null $title
 * @property string $number
 * @property string $account
 * @property string|null $prompt
 * @property string $parameters
 * @property string $extension
 * @property string|null $selectGen
 * @property string|null $svg
 * @property-read Designs|null $design
 * @property-read User|null $user
 * @method static Builder<static>|Pictures newModelQuery()
 * @method static Builder<static>|Pictures newQuery()
 * @method static Builder<static>|Pictures query()
 * @method static Builder<static>|Pictures whereAbort($value)
 * @method static Builder<static>|Pictures whereAccount($value)
 * @method static Builder<static>|Pictures whereDateGenerate($value)
 * @method static Builder<static>|Pictures whereDateUpdate($value)
 * @method static Builder<static>|Pictures whereExtension($value)
 * @method static Builder<static>|Pictures whereId($value)
 * @method static Builder<static>|Pictures whereNumber($value)
 * @method static Builder<static>|Pictures whereParameters($value)
 * @method static Builder<static>|Pictures wherePosition($value)
 * @method static Builder<static>|Pictures wherePriority($value)
 * @method static Builder<static>|Pictures wherePrompt($value)
 * @method static Builder<static>|Pictures whereSelectGen($value)
 * @method static Builder<static>|Pictures whereSvg($value)
 * @method static Builder<static>|Pictures whereTitle($value)
 * @method static Builder<static>|Pictures whereUid($value)
 * @mixin Builder
 */
class Pictures extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'sora';
    protected $table = 'pictures';
    protected $fillable = [
        'priority',
        'position',
        'abort',
        'date_generate',
        'date_update',
        'uid',
        'title',
        'number',
        'account',
        'prompt',
        'parameters',
        'extension',
        'selectGen',
        'svg'
    ];

    public function design(): HasOne
    {
        return $this->hasOne(Designs::class, 'number', 'number');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }
}
