<?php namespace App\Models\Dev\YM;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $offer_id
 * @property int $property_id
 * @property int $pvid
 * @property string $value
 * @property int|null $uid
 * @property-read Products $product
 * @property-read Properties $property
 * @property-read PV|null $pv
 * @property-read Units|null $unit
 * @method static Builder<static>|PPV newModelQuery()
 * @method static Builder<static>|PPV newQuery()
 * @method static Builder<static>|PPV query()
 * @method static Builder<static>|PPV whereOfferId($value)
 * @method static Builder<static>|PPV wherePropertyId($value)
 * @method static Builder<static>|PPV wherePvid($value)
 * @method static Builder<static>|PPV whereUid($value)
 * @method static Builder<static>|PPV whereValue($value)
 * @mixin Builder
 */
class PPV extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_ppv';
    protected $fillable = [
        'offer_id',
        'property_id',
        'pvid',
        'value',
        'uid'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'offer_id', 'offerId');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Properties::class, 'property_id', 'id');
    }

    public function pv(): BelongsTo
    {
        return $this->belongsTo(PV::class, 'pvid', 'id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Units::class, 'uid', 'id');
    }
}
