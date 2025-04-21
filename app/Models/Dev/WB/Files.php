<?php namespace App\Models\Dev\WB;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $nmID
 * @property string $last_request
 * @property string $type
 * @property string $url
 * @property-read Products $product
 * @method static Builder<static>|Files newModelQuery()
 * @method static Builder<static>|Files newQuery()
 * @method static Builder<static>|Files query()
 * @method static Builder<static>|Files whereLastRequest($value)
 * @method static Builder<static>|Files whereNmID($value)
 * @method static Builder<static>|Files whereType($value)
 * @method static Builder<static>|Files whereUrl($value)
 * @mixin Builder
 */
class Files extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'wb_files';
    protected $fillable = [
        'nmID',
        'last_request',
        'type',
        'url'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'nmID', 'nmID');
    }
}
