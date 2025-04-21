<?php namespace App\Models\Dev\YM;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $offer_id
 * @property string $type
 * @property string $message
 * @property string $comment
 * @property-read Products $product
 * @method static Builder<static>|Notices newModelQuery()
 * @method static Builder<static>|Notices newQuery()
 * @method static Builder<static>|Notices query()
 * @method static Builder<static>|Notices whereComment($value)
 * @method static Builder<static>|Notices whereMessage($value)
 * @method static Builder<static>|Notices whereOfferId($value)
 * @method static Builder<static>|Notices whereType($value)
 * @mixin Builder
 */
class Notices extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_notices';
    protected $fillable = [
        'offer_id',
        'type',
        'message',
        'comment'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'offer_id', 'offerId');
    }
}
