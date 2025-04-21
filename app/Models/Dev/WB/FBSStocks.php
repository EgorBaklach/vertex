<?php namespace App\Models\Dev\WB;

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
 * @property int $officeId
 * @property int $tid
 * @property string $name
 * @property int $cargoType
 * @property int $deliveryType
 * @property int $total
 * @property-read FBSOffices $office
 * @property-read MarketplaceApiKey $token
 * @method static Builder<static>|FBSStocks newModelQuery()
 * @method static Builder<static>|FBSStocks newQuery()
 * @method static Builder<static>|FBSStocks query()
 * @method static Builder<static>|FBSStocks whereActive($value)
 * @method static Builder<static>|FBSStocks whereCargoType($value)
 * @method static Builder<static>|FBSStocks whereDeliveryType($value)
 * @method static Builder<static>|FBSStocks whereId($value)
 * @method static Builder<static>|FBSStocks whereLastRequest($value)
 * @method static Builder<static>|FBSStocks whereName($value)
 * @method static Builder<static>|FBSStocks whereOfficeId($value)
 * @method static Builder<static>|FBSStocks whereTid($value)
 * @method static Builder<static>|FBSStocks whereTotal($value)
 * @mixin Builder
 */
class FBSStocks extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'wb_fbs_stocks';
    protected $fillable = [
        'last_request',
        'active',
        'officeId',
        'tid',
        'name',
        'cargoType',
        'deliveryType',
        'total'
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(FBSOffices::class, 'officeId', 'id');
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(MarketplaceApiKey::class, 'tid', 'id');
    }
}
