<?php namespace App\Models\Dev\YM;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $offer_id
 * @property int $sid
 * @property string $updatedAt
 * @property string $type
 * @property int $count
 * @property string|null $turnoverType
 * @property float|null $turnoverDays
 * @property-read Products $product
 * @property-read FBYStocks $stock
 * @method static Builder<static>|FBYAmounts newModelQuery()
 * @method static Builder<static>|FBYAmounts newQuery()
 * @method static Builder<static>|FBYAmounts query()
 * @method static Builder<static>|FBYAmounts whereCount($value)
 * @method static Builder<static>|FBYAmounts whereOfferId($value)
 * @method static Builder<static>|FBYAmounts whereSid($value)
 * @method static Builder<static>|FBYAmounts whereTurnoverDays($value)
 * @method static Builder<static>|FBYAmounts whereTurnoverType($value)
 * @method static Builder<static>|FBYAmounts whereType($value)
 * @method static Builder<static>|FBYAmounts whereUpdatedAt($value)
 * @mixin Builder
 */
class FBYAmounts extends AmountsAbstract
{
    protected $table = 'ym_fby_amounts';

    const logHistoryName = 'fby.csv';

    public function __construct(array $attributes = [])
    {
        array_push($this->fillable, 'turnoverType', 'turnoverDays'); parent::__construct($attributes);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(FBYStocks::class, 'sid', 'id');
    }
}
