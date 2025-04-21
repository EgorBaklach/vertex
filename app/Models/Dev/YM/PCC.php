<?php namespace App\Models\Dev\YM;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $offer_id
 * @property string $commodity_code
 * @property-read CommodityCodes $commodityCode
 * @property-read Products $product
 * @method static Builder<static>|PCC newModelQuery()
 * @method static Builder<static>|PCC newQuery()
 * @method static Builder<static>|PCC query()
 * @method static Builder<static>|PCC whereCommodityCode($value)
 * @method static Builder<static>|PCC whereOfferId($value)
 * @mixin Builder
 */
class PCC extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_pcc';
    protected $fillable = [
        'offer_id',
        'commodity_code',
    ];

    public function commodityCode(): BelongsTo
    {
        return $this->belongsTo(CommodityCodes::class, 'commodity_code', 'code');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'offer_id', 'offerId');
    }
}
