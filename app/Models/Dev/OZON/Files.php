<?php namespace App\Models\Dev\OZON;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $pid
 * @property string $last_request
 * @property string $type
 * @property string $url
 * @property string $caption
 * @property-read Products $token
 * @method static Builder<static>|Files newModelQuery()
 * @method static Builder<static>|Files newQuery()
 * @method static Builder<static>|Files query()
 * @method static Builder<static>|Files whereCaption($value)
 * @method static Builder<static>|Files whereLastRequest($value)
 * @method static Builder<static>|Files wherePid($value)
 * @method static Builder<static>|Files whereType($value)
 * @method static Builder<static>|Files whereUrl($value)
 * @mixin Builder
 */
class Files extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ozon_files';
    protected $fillable = [
        'pid',
        'last_request',
        'type',
        'url',
        'caption'
    ];

    public function token(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'pid', 'id');
    }
}
