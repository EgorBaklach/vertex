<?php namespace App\Models\Management\Designs;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $article
 * @property string|null $number
 * @property string|null $is_block
 * @property string|null $name
 * @property-read Collection<int, Groups> $groups
 * @property-read int|null $groups_count
 * @property-read Collection<int, Colors> $markets
 * @property-read int|null $markets_count
 * @method static Builder<static>|Designs newModelQuery()
 * @method static Builder<static>|Designs newQuery()
 * @method static Builder<static>|Designs query()
 * @method static Builder<static>|Designs whereArticle($value)
 * @method static Builder<static>|Designs whereIsBlock($value)
 * @method static Builder<static>|Designs whereName($value)
 * @method static Builder<static>|Designs whereNumber($value)
 * @mixin Builder
 */
class Designs extends Model
{
    use HasFactory;

    protected $primaryKey = 'article';
    protected $keyType = 'string';

    public $incrementing = false;
    public $timestamps = false;

    protected $connection = 'management';
    protected $table = 'designs';
    protected $fillable = [
        'article',
        'is_block',
        'name',
        'design_number'
    ];

    public function colors(): HasMany
    {
        return $this->hasMany(Colors::class, 'design', 'article');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Groups::class, 'design', 'article');
    }
}
