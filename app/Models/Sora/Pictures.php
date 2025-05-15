<?php namespace App\Models\Sora;

use App\Models\Management\Designs\Designs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string|null $status
 * @property string|null $abort
 * @property string $number
 * @property string $account
 * @property string $prompt
 * @property string $extension
 * @property string|null $selectGen
 * @property string|null $svg
 * @property-read Designs|null $design
 * @method static Builder<static>|Pictures newModelQuery()
 * @method static Builder<static>|Pictures newQuery()
 * @method static Builder<static>|Pictures query()
 * @method static Builder<static>|Pictures whereAbort($value)
 * @method static Builder<static>|Pictures whereAccount($value)
 * @method static Builder<static>|Pictures whereExtension($value)
 * @method static Builder<static>|Pictures whereId($value)
 * @method static Builder<static>|Pictures whereNumber($value)
 * @method static Builder<static>|Pictures wherePrompt($value)
 * @method static Builder<static>|Pictures whereSelectGen($value)
 * @method static Builder<static>|Pictures whereStatus($value)
 * @method static Builder<static>|Pictures whereSvg($value)
 * @mixin Builder
 */
class Pictures extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'sora';
    protected $table = 'pictures';
    protected $fillable = [
        'status',
        'abort',
        'number',
        'account',
        'prompt',
        'extension',
        'selectGen',
        'svg'
    ];

    public function design(): HasOne
    {
        return $this->hasOne(Designs::class, 'number', 'number');
    }
}
