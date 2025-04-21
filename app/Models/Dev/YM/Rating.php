<?php namespace App\Models\Dev\YM;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $offer_id
 * @property string $last_request
 * @property string|null $status
 * @property int|null $rating
 * @property int $average
 * @property-read Products $product
 * @method static Builder<static>|Rating newModelQuery()
 * @method static Builder<static>|Rating newQuery()
 * @method static Builder<static>|Rating query()
 * @method static Builder<static>|Rating whereAverage($value)
 * @method static Builder<static>|Rating whereLastRequest($value)
 * @method static Builder<static>|Rating whereOfferId($value)
 * @method static Builder<static>|Rating whereRating($value)
 * @method static Builder<static>|Rating whereStatus($value)
 * @mixin Builder
 */
class Rating extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_rating';
    protected $fillable = [
        'offer_id',
        'last_request',
        'status',
        'rating',
        'average'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'offer_id', 'offerId');
    }
}
