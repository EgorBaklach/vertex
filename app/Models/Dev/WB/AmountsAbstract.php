<?php namespace App\Models\Dev\WB;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Sizes $sku
 * @property int $sid
 * @property int $amount
 * @property-read FBOStocks|FBSStocks $stock
 * @method static Builder<static>|FBOAmounts newModelQuery()
 * @method static Builder<static>|FBOAmounts newQuery()
 * @method static Builder<static>|FBOAmounts query()
 * @method static Builder<static>|FBOAmounts whereAmount($value)
 * @method static Builder<static>|FBOAmounts whereSid($value)
 * @method static Builder<static>|FBOAmounts whereSku($value)
 * @mixin Builder
 */
abstract class AmountsAbstract extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $fillable = [
        'sku',
        'sid',
        'amount'
    ];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sizes::class, 'sku', 'sku');
    }

    abstract  public function stock(): BelongsTo;
}
