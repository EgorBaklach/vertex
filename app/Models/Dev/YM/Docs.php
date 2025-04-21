<?php namespace App\Models\Dev\YM;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $offer_id
 * @property string $last_request
 * @property string $type
 * @property string $state
 * @property string $value
 * @property string|null $title
 * @property-read Products $product
 * @method static Builder<static>|Docs newModelQuery()
 * @method static Builder<static>|Docs newQuery()
 * @method static Builder<static>|Docs query()
 * @method static Builder<static>|Docs whereLastRequest($value)
 * @method static Builder<static>|Docs whereOfferId($value)
 * @method static Builder<static>|Docs whereState($value)
 * @method static Builder<static>|Docs whereTitle($value)
 * @method static Builder<static>|Docs whereType($value)
 * @method static Builder<static>|Docs whereValue($value)
 * @mixin Builder
 */
class Docs extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_docs';
    protected $fillable = [
        'offer_id',
        'last_request',
        'type',
        'state',
        'value',
        'title'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'offer_id', 'offerId');
    }
}
