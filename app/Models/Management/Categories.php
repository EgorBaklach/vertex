<?php namespace App\Models\Management;

use App\Models\Dev\OZON\Categories as OZONCategories;
use App\Models\Dev\OZON\Types;
use App\Models\Dev\WB\Categories as WBCategories;
use App\Models\Dev\YM\Categories as YMCategories;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $wb_cid
 * @property int $ozon_tid
 * @property int $ozon_cid
 * @property int $ym_cid
 * @property string $name
 * @property-read Types $ozon_type
 * @property-read OZONCategories $ozon_category
 * @property-read WBCategories $wb
 * @property-read YMCategories $ym
 * @method static Builder<static>|Categories newModelQuery()
 * @method static Builder<static>|Categories newQuery()
 * @method static Builder<static>|Categories query()
 * @method static Builder<static>|Categories whereId($value)
 * @method static Builder<static>|Categories whereName($value)
 * @method static Builder<static>|Categories whereOzonTid($value)
 * @method static Builder<static>|Categories whereOzonCid($value)
 * @method static Builder<static>|Categories whereWbCid($value)
 * @method static Builder<static>|Categories whereYmCid($value)
 * @mixin Builder
 */
class Categories extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $connection = 'management';
    protected $table = 'categories';
    protected $fillable = [
        'wb_cid',
        'ozon_tid',
        'ozon_cid',
        'ym_cid',
        'name'
    ];

    public function wb(): BelongsTo
    {
        return $this->belongsTo(WBCategories::class, 'wb_cid', 'id');
    }

    public function ozon_type(): BelongsTo
    {
        return $this->belongsTo(Types::class, 'ozon_tid', 'id');
    }

    public function ozon_category(): BelongsTo
    {
        return $this->belongsTo(OZONCategories::class, 'ozon_cid', 'id');
    }

    public function ym(): BelongsTo
    {
        return $this->belongsTo(YMCategories::class, 'ym_cid', 'id');
    }
}
