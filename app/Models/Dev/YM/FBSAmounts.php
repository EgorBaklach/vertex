<?php namespace App\Models\Dev\YM;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $offer_id
 * @property int $sid
 * @property string $updatedAt
 * @property string $type
 * @property int $count
 * @property-read Products $product
 * @property-read FBSStocks $stock
 * @method static Builder<static>|FBSAmounts newModelQuery()
 * @method static Builder<static>|FBSAmounts newQuery()
 * @method static Builder<static>|FBSAmounts query()
 * @method static Builder<static>|FBSAmounts whereCount($value)
 * @method static Builder<static>|FBSAmounts whereOfferId($value)
 * @method static Builder<static>|FBSAmounts whereSid($value)
 * @method static Builder<static>|FBSAmounts whereType($value)
 * @method static Builder<static>|FBSAmounts whereUpdatedAt($value)
 * @mixin Builder
 */
class FBSAmounts extends AmountsAbstract
{
    protected $table = 'ym_fbs_amounts';

    const logHistoryName = 'fbs.csv';

    public function stock(): BelongsTo
    {
        return $this->belongsTo(FBSStocks::class, 'sid', 'id');
    }
}
