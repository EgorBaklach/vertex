<?php namespace App\Models\Dev\OZON;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $last_request
 * @property string|null $active
 * @property string|null $disabled
 * @property string $name
 * @property int $cnt
 * @property-read Collection<int, Categories> $categories
 * @property-read int|null $categories_count
 * @property-read Collection<int, Properties> $properties
 * @property-read int|null $properties_count
 * @method static Builder<static>|Types newModelQuery()
 * @method static Builder<static>|Types newQuery()
 * @method static Builder<static>|Types query()
 * @method static Builder<static>|Types whereActive($value)
 * @method static Builder<static>|Types whereDisabled($value)
 * @method static Builder<static>|Types whereId($value)
 * @method static Builder<static>|Types whereLastRequest($value)
 * @method static Builder<static>|Types whereName($value)
 * @mixin Builder
 */
class Types extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ozon_types';
    protected $fillable = [
        'last_request',
        'active',
        'disabled',
        'name',
        'cnt'
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Categories::class, 'ozon_ct', 'tid', 'cid')->where('active', 'Y')->whereNull('disabled');
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Properties::class, 'ozon_ctp', 'tid', 'pid');
    }
}
