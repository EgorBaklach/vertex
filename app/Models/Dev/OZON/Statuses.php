<?php namespace App\Models\Dev\OZON;

use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $pid
 * @property string $updated_at
 * @property string $last_request
 * @property string|null $active
 * @property string|null $is_created
 * @property string|null $moderate_status Статус модерации
 * @property string $status Статус товара
 * @property string|null $status_description Описание статуса товара
 * @property string|null $status_failed Статус товара, в котором возникла ошибка
 * @property string|null $status_name Название статуса товара
 * @property string|null $status_tooltip
 * @property string|null $validation_status Статус валидации
 * @property-read Products $product
 * @method static Builder<static>|Statuses newModelQuery()
 * @method static Builder<static>|Statuses newQuery()
 * @method static Builder<static>|Statuses query()
 * @method static Builder<static>|Statuses whereActive($value)
 * @method static Builder<static>|Statuses whereIsCreated($value)
 * @method static Builder<static>|Statuses whereLastRequest($value)
 * @method static Builder<static>|Statuses whereModerateStatus($value)
 * @method static Builder<static>|Statuses wherePid($value)
 * @method static Builder<static>|Statuses whereStatus($value)
 * @method static Builder<static>|Statuses whereStatusDescription($value)
 * @method static Builder<static>|Statuses whereStatusFailed($value)
 * @method static Builder<static>|Statuses whereStatusName($value)
 * @method static Builder<static>|Statuses whereStatusTooltip($value)
 * @method static Builder<static>|Statuses whereUpdatedAt($value)
 * @method static Builder<static>|Statuses whereValidationStatus($value)
 * @mixin Builder
 */
class Statuses extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ozon_statuses';
    protected $fillable = [
        'pid',
        'updated_at',
        'last_request',
        'active',
        'is_created',
        'moderate_status',
        'status',
        'status_description',
        'status_failed',
        'status_name',
        'status_tooltip',
        'validation_status'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'pid', 'id');
    }
}
