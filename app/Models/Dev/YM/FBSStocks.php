<?php namespace App\Models\Dev\YM;

use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $last_request
 * @property string|null $active
 * @property string $name
 * @property int $tid
 * @property string|null $express
 * @property string $city
 * @property string $street
 * @property string $number
 * @property float $latitude
 * @property float $longitude
 * @property-read MarketplaceApiKey $token
 * @method static Builder<static>|FBSStocks newModelQuery()
 * @method static Builder<static>|FBSStocks newQuery()
 * @method static Builder<static>|FBSStocks query()
 * @method static Builder<static>|FBSStocks whereActive($value)
 * @method static Builder<static>|FBSStocks whereCity($value)
 * @method static Builder<static>|FBSStocks whereExpress($value)
 * @method static Builder<static>|FBSStocks whereId($value)
 * @method static Builder<static>|FBSStocks whereLastRequest($value)
 * @method static Builder<static>|FBSStocks whereLatitude($value)
 * @method static Builder<static>|FBSStocks whereLongitude($value)
 * @method static Builder<static>|FBSStocks whereName($value)
 * @method static Builder<static>|FBSStocks whereNumber($value)
 * @method static Builder<static>|FBSStocks whereStreet($value)
 * @method static Builder<static>|FBSStocks whereTid($value)
 * @mixin Builder
 */
class FBSStocks extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_fbs_stocks';
    protected $fillable = [
        'last_request',
        'active',
        'name',
        'tid',
        'express',
        'city',
        'street',
        'number',
        'latitude',
        'longitude'
    ];

    public function token(): BelongsTo
    {
        return $this->belongsTo(MarketplaceApiKey::class, 'tid', 'id');
    }
}
