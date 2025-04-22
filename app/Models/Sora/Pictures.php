<?php namespace App\Models\Sora;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $active
 * @property string $article
 * @property string|null $selectGen
 * @property string|null $svg
 * @method static Builder<static>|Pictures newModelQuery()
 * @method static Builder<static>|Pictures newQuery()
 * @method static Builder<static>|Pictures query()
 * @method static Builder<static>|Pictures whereArticle($value)
 * @method static Builder<static>|Pictures whereId($value)
 * @method static Builder<static>|Pictures whereSelectGen($value)
 * @method static Builder<static>|Pictures whereActive($value)
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
        'active',
        'article',
        'selectGen',
        'svg'
    ];
}
