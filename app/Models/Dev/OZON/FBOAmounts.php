<?php namespace App\Models\Dev\OZON;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Prices $sku
 * @property int $sid
 * @property string $type
 * @property int $amount
 * @property-read FBOStocks|null $stock
 * @method static Builder<static>|FBOAmounts newModelQuery()
 * @method static Builder<static>|FBOAmounts newQuery()
 * @method static Builder<static>|FBOAmounts query()
 * @method static Builder<static>|FBOAmounts whereAmount($value)
 * @method static Builder<static>|FBOAmounts whereSid($value)
 * @method static Builder<static>|FBOAmounts whereSku($value)
 * @method static Builder<static>|FBOAmounts whereType($value)
 * @mixin Builder
 */
class FBOAmounts extends AmountsAbstract
{
    protected $table = 'ozon_fbo_amounts';

    public function stock(): BelongsTo
    {
        return $this->belongsTo(FBOStocks::class, 'sid', 'id');
    }
}
