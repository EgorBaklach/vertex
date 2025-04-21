<?php namespace App\Models\Dev\OZON;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $product_id
 * @property int|null $property_id
 * @property string $code Код ошибки
 * @property string|null $field Поле, в котором найдена ошибка
 * @property string $level
 * @property string $state Статус товара, в котором произошла ошибка
 * @property string $description
 * @property string $message
 * @property string|null $params В каких параметрах допущена ошибка
 * @property-read Products $product
 * @property-read Properties|null $property
 * @method static Builder<static>|Errors newModelQuery()
 * @method static Builder<static>|Errors newQuery()
 * @method static Builder<static>|Errors query()
 * @method static Builder<static>|Errors whereCode($value)
 * @method static Builder<static>|Errors whereDescription($value)
 * @method static Builder<static>|Errors whereField($value)
 * @method static Builder<static>|Errors whereLevel($value)
 * @method static Builder<static>|Errors whereMessage($value)
 * @method static Builder<static>|Errors whereParams($value)
 * @method static Builder<static>|Errors whereProductId($value)
 * @method static Builder<static>|Errors wherePropertyId($value)
 * @method static Builder<static>|Errors whereState($value)
 * @mixin Builder
 */
class Errors extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ozon_errors';
    protected $fillable = [
        'product_id',
        'property_id',
        'code',
        'field',
        'level',
        'state',
        'description',
        'message',
        'params'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id', 'id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Properties::class, 'property_id', 'id');
    }
}
