<?php namespace App\Models\Dev\YM;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $last_request
 * @property string|null $active
 * @property string $name
 * @property string $city
 * @property string $street
 * @property string $number
 * @property string|null $building
 * @property string|null $block
 * @property float $latitude
 * @property float $longitude
 * @method static Builder<static>|FBYStocks newModelQuery()
 * @method static Builder<static>|FBYStocks newQuery()
 * @method static Builder<static>|FBYStocks query()
 * @method static Builder<static>|FBYStocks whereActive($value)
 * @method static Builder<static>|FBYStocks whereBlock($value)
 * @method static Builder<static>|FBYStocks whereBuilding($value)
 * @method static Builder<static>|FBYStocks whereCity($value)
 * @method static Builder<static>|FBYStocks whereId($value)
 * @method static Builder<static>|FBYStocks whereLastRequest($value)
 * @method static Builder<static>|FBYStocks whereLatitude($value)
 * @method static Builder<static>|FBYStocks whereLongitude($value)
 * @method static Builder<static>|FBYStocks whereName($value)
 * @method static Builder<static>|FBYStocks whereNumber($value)
 * @method static Builder<static>|FBYStocks whereStreet($value)
 * @mixin Builder
 */
class FBYStocks extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_fby_stocks';
    protected $fillable = [
        'last_request',
        'active',
        'name',
        'city',
        'street',
        'number',
        'building',
        'block',
        'latitude',
        'longitude'
    ];
}
