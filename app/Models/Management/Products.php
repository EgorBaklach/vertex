<?php namespace App\Models\Management;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $create_date
 * @property string|null $active
 * @property int $cid
 * @property string $name
 * @property string $article
 * @property string|null $barcode
 * @property float $discount_price
 * @property float|null $price
 * @property string $unit_dimension
 * @property string $unit_weight
 * @property string $nds
 * @property int $length
 * @property int $height
 * @property int $width
 * @property int $weight
 * @property-read Categories $category
 * @method static Builder<static>|Products newModelQuery()
 * @method static Builder<static>|Products newQuery()
 * @method static Builder<static>|Products query()
 * @method static Builder<static>|Products whereActive($value)
 * @method static Builder<static>|Products whereArticle($value)
 * @method static Builder<static>|Products whereBarcode($value)
 * @method static Builder<static>|Products whereCid($value)
 * @method static Builder<static>|Products whereCreateDate($value)
 * @method static Builder<static>|Products whereDiscountPrice($value)
 * @method static Builder<static>|Products whereHeight($value)
 * @method static Builder<static>|Products whereId($value)
 * @method static Builder<static>|Products whereLength($value)
 * @method static Builder<static>|Products whereName($value)
 * @method static Builder<static>|Products whereNds($value)
 * @method static Builder<static>|Products wherePrice($value)
 * @method static Builder<static>|Products whereUnitDimension($value)
 * @method static Builder<static>|Products whereUnitWeight($value)
 * @method static Builder<static>|Products whereWeight($value)
 * @method static Builder<static>|Products whereWidth($value)
 * @mixin Builder
 */
class Products extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'management';
    protected $table = 'products';
    protected $fillable = [
        'create_date',
        'active',
        'cid',
        'name',
        'article',
        'barcode',
        'discount_price',
        'price',
        'unit_dimension',
        'unit_weight',
        'nds',
        'length',
        'height',
        'width',
        'weight'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categories::class, 'cid', 'id');
    }
}
