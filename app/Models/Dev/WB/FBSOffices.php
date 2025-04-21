<?php namespace App\Models\Dev\WB;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $last_request
 * @property string|null $active
 * @property string $address
 * @property string $name
 * @property string $city
 * @property float $longitude
 * @property float $latitude
 * @property int $cargoType
 * @property int $deliveryType
 * @property string|null $selected
 * @property-read Collection<int, FBSStocks> $stocks
 * @property-read int|null $stocks_count
 * @method static Builder<static>|FBSOffices newModelQuery()
 * @method static Builder<static>|FBSOffices newQuery()
 * @method static Builder<static>|FBSOffices query()
 * @method static Builder<static>|FBSOffices whereActive($value)
 * @method static Builder<static>|FBSOffices whereAddress($value)
 * @method static Builder<static>|FBSOffices whereCargoType($value)
 * @method static Builder<static>|FBSOffices whereCity($value)
 * @method static Builder<static>|FBSOffices whereDeliveryType($value)
 * @method static Builder<static>|FBSOffices whereId($value)
 * @method static Builder<static>|FBSOffices whereLastRequest($value)
 * @method static Builder<static>|FBSOffices whereLatitude($value)
 * @method static Builder<static>|FBSOffices whereLongitude($value)
 * @method static Builder<static>|FBSOffices whereName($value)
 * @method static Builder<static>|FBSOffices whereSelected($value)
 * @mixin Builder
 */
class FBSOffices extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'wb_fbs_offices';
    protected $fillable = [
        'last_request',
        'active',
        'address',
        'name',
        'city',
        'longitude',
        'latitude',
        'cargoType',
        'deliveryType',
        'selected'
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(FBSStocks::class, 'officeId', 'id');
    }
}
