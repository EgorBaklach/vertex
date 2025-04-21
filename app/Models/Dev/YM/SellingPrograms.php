<?php namespace App\Models\Dev\YM;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $offer_id
 * @property string $program
 * @property string $status
 * @property-read Products $product
 * @method static Builder<static>|SellingPrograms newModelQuery()
 * @method static Builder<static>|SellingPrograms newQuery()
 * @method static Builder<static>|SellingPrograms query()
 * @method static Builder<static>|SellingPrograms whereOfferId($value)
 * @method static Builder<static>|SellingPrograms whereProgram($value)
 * @method static Builder<static>|SellingPrograms whereStatus($value)
 * @mixin Builder
 */
class SellingPrograms extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_selling_programs';
    protected $fillable = [
        'offer_id',
        'program',
        'status'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'offer_id', 'offerId');
    }
}
