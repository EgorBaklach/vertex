<?php namespace App\Models\Dev\OZON;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Prices $sku
 * @property int $sid
 * @property string $type
 * @property int $amount
 * @property-read FBSStocks|null $stock
 * @method static Builder<static>|FBSAmounts newModelQuery()
 * @method static Builder<static>|FBSAmounts newQuery()
 * @method static Builder<static>|FBSAmounts query()
 * @method static Builder<static>|FBSAmounts whereAmount($value)
 * @method static Builder<static>|FBSAmounts whereSid($value)
 * @method static Builder<static>|FBSAmounts whereSku($value)
 * @method static Builder<static>|FBSAmounts whereType($value)
 * @mixin Builder
 */
class FBSAmounts extends AmountsAbstract
{
    protected $table = 'ozon_fbs_amounts';

    public function stock(): BelongsTo
    {
        return $this->belongsTo(FBSStocks::class, 'sid', 'id');
    }
}
