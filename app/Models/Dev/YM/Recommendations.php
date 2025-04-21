<?php namespace App\Models\Dev\YM;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $category_id
 * @property int $property_id
 * @property string $product_offer_id
 * @property int|null $RECOGNIZED_VENDOR напишите название производителя так, как его пишет сам производитель (параметр vendor)
 * @property int|null $PICTURE_COUNT добавьте изображения (параметр pictures)
 * @property int|null $FIRST_PICTURE_SIZE замените первое изображение более крупным (параметр pictures)
 * @property int|null $TITLE_LENGTH измените название (параметр name). Составьте название по схеме: тип + бренд или производитель + модель + особенности, если есть (размер, вес, цвет)
 * @property int|null $DESCRIPTION_LENGTH добавьте описание рекомендуемого размера (параметр description)
 * @property int|null $AVERAGE_PICTURE_SIZE замените все изображения на изображения высокого качества (параметр pictures)
 * @property int|null $FIRST_VIDEO_LENGTH добавьте первое видео рекомендуемой длины (параметр videos)
 * @property int|null $FIRST_VIDEO_SIZE замените первое видео на видео высокого качества (параметр videos)
 * @property int|null $AVERAGE_VIDEO_SIZE замените все видео на видео высокого качества (параметр videos)
 * @property int|null $VIDEO_COUNT добавьте хотя бы одно видео (параметр videos)
 * @property int|null $MAIN заполните ключевые характеристики товара
 * @property int|null $ADDITIONAL заполните дополнительные характеристики товара
 * @property int|null $DISTINCTIVE заполните характеристики, которыми отличаются друг от друга варианты товара
 * @property int|null $HAS_VIDEO
 * @property int|null $FILTERABLE
 * @property int|null $HAS_DESCRIPTION
 * @property int|null $HAS_BARCODE
 * @property-read Categories|null $category
 * @property-read Products|null $product
 * @property-read Properties|null $property
 * @method static Builder<static>|Recommendations newModelQuery()
 * @method static Builder<static>|Recommendations newQuery()
 * @method static Builder<static>|Recommendations query()
 * @method static Builder<static>|Recommendations whereADDITIONAL($value)
 * @method static Builder<static>|Recommendations whereAVERAGEPICTURESIZE($value)
 * @method static Builder<static>|Recommendations whereAVERAGEVIDEOSIZE($value)
 * @method static Builder<static>|Recommendations whereCategoryId($value)
 * @method static Builder<static>|Recommendations whereDESCRIPTIONLENGTH($value)
 * @method static Builder<static>|Recommendations whereDISTINCTIVE($value)
 * @method static Builder<static>|Recommendations whereFILTERABLE($value)
 * @method static Builder<static>|Recommendations whereFIRSTPICTURESIZE($value)
 * @method static Builder<static>|Recommendations whereFIRSTVIDEOLENGTH($value)
 * @method static Builder<static>|Recommendations whereFIRSTVIDEOSIZE($value)
 * @method static Builder<static>|Recommendations whereHASBARCODE($value)
 * @method static Builder<static>|Recommendations whereHASDESCRIPTION($value)
 * @method static Builder<static>|Recommendations whereHASVIDEO($value)
 * @method static Builder<static>|Recommendations whereMAIN($value)
 * @method static Builder<static>|Recommendations wherePICTURECOUNT($value)
 * @method static Builder<static>|Recommendations whereProductOfferId($value)
 * @method static Builder<static>|Recommendations wherePropertyId($value)
 * @method static Builder<static>|Recommendations whereRECOGNIZEDVENDOR($value)
 * @method static Builder<static>|Recommendations whereTITLELENGTH($value)
 * @method static Builder<static>|Recommendations whereVIDEOCOUNT($value)
 * @mixin Builder
 */
class Recommendations extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ym_recommendations';
    protected $fillable = [
        'category_id',
        'property_id',
        'product_offer_id',
        'RECOGNIZED_VENDOR',
        'PICTURE_COUNT',
        'FIRST_PICTURE_SIZE',
        'TITLE_LENGTH',
        'DESCRIPTION_LENGTH',
        'AVERAGE_PICTURE_SIZE',
        'FIRST_VIDEO_LENGTH',
        'FIRST_VIDEO_SIZE',
        'AVERAGE_VIDEO_SIZE',
        'VIDEO_COUNT',
        'MAIN',
        'ADDITIONAL',
        'DISTINCTIVE',
        'HAS_VIDEO',
        'FILTERABLE',
        'HAS_DESCRIPTION',
        'HAS_BARCODE'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categories::class, 'category_id', 'id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Properties::class, 'property_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_offer_id', 'offerId');
    }
}
