<?php namespace App\Models\Dev\YM;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $offer_id
 * @property string $type Тип годности
 * @property string $unit
 * @property int $period
 * @property string|null $comment
 * @property-read Products $product
 * @method static Builder<static>|Times newModelQuery()
 * @method static Builder<static>|Times newQuery()
 * @method static Builder<static>|Times query()
 * @method static Builder<static>|Times whereComment($value)
 * @method static Builder<static>|Times whereOfferId($value)
 * @method static Builder<static>|Times wherePeriod($value)
 * @method static Builder<static>|Times whereType($value)
 * @method static Builder<static>|Times whereUnit($value)
 * @mixin Builder
 */
class Times extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_times';
    protected $fillable = [
        'offer_id',
        'type',
        'unit',
        'period',
        'comment'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'offer_id', 'offerId');
    }
}
