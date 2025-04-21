<?php namespace App\Models\Dev\YM;

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
 * @property string $name
 * @property string $fullName
 * @property-read Collection<int, Categories> $categories
 * @property-read int|null $categories_count
 * @property-read Collection<int, Properties> $properties
 * @property-read int|null $properties_count
 * @method static Builder<static>|Units newModelQuery()
 * @method static Builder<static>|Units newQuery()
 * @method static Builder<static>|Units query()
 * @method static Builder<static>|Units whereActive($value)
 * @method static Builder<static>|Units whereFullName($value)
 * @method static Builder<static>|Units whereId($value)
 * @method static Builder<static>|Units whereLastRequest($value)
 * @method static Builder<static>|Units whereName($value)
 * @mixin Builder
 */
class Units extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_units';
    protected $fillable = [
        'last_request',
        'active',
        'name',
        'fullName'
    ];

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Properties::class, 'ym_pu', 'uid', 'pid');
    }
}
