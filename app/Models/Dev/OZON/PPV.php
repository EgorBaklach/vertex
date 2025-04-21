<?php namespace App\Models\Dev\OZON;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $product_id
 * @property int $property_id
 * @property int $pvid
 * @property string|null $value
 * @property string|null $is_complex
 * @property int|null $complex_id
 * @property-read Products $product
 * @property-read Properties $property
 * @property-read PV|null $pv
 * @method static Builder<static>|PPV newModelQuery()
 * @method static Builder<static>|PPV newQuery()
 * @method static Builder<static>|PPV query()
 * @method static Builder<static>|PPV whereComplexId($value)
 * @method static Builder<static>|PPV whereIsComplex($value)
 * @method static Builder<static>|PPV whereProductId($value)
 * @method static Builder<static>|PPV wherePropertyId($value)
 * @method static Builder<static>|PPV wherePvid($value)
 * @method static Builder<static>|PPV whereValue($value)
 * @mixin Builder
 */
class PPV extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ozon_ppv';
    protected $fillable = [
        'product_id',
        'property_id',
        'pvid',
        'value',
        'is_complex',
        'complex_id'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id', 'id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Properties::class, 'property_id', 'id');
    }

    public function pv(): BelongsTo
    {
        return $this->belongsTo(PV::class, 'pvid', 'id');
    }
}
