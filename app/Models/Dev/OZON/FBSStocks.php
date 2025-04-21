<?php namespace App\Models\Dev\OZON;

use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\Traits\CustomQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tid
 * @property string $last_request
 * @property string|null $active
 * @property string $name
 * @property string|null $is_rfbs Признак работы склада по схеме rFBS
 * @property string|null $is_able_to_set_price Склад может устанавливать цену
 * @property string|null $has_entrusted_acceptance Признак доверительной приёмки
 * @property string $dropoff_point_id Идентификатор DropOff-точки
 * @property int $dropoff_timeslot_id Идентификатор временного слота для DropOff
 * @property string|null $first_mile_is_changing Признак, что настройки склада обновляются
 * @property string $first_mile_type Тип первой мили
 * @property string|null $is_kgt Признак, что склад принимает крупногабаритные товары
 * @property string|null $can_print_act_in_advance Возможность печати акта приёма-передачи заранее
 * @property int $min_working_days Количество рабочих дней склада
 * @property string|null $is_karantin Признак, что склад не работает из-за карантина
 * @property string|null $has_postings_limit Признак наличия лимита минимального количества заказов
 * @property int $postings_limit Значение лимита
 * @property string $working_days Рабочие дни склада
 * @property int $min_postings_limit Минимальное значение лимита — количество заказов, которые можно привезти в одной поставке
 * @property string|null $is_timetable_editable Признак, что можно менять расписание работы складов
 * @property string $status Статус склада
 * @property string|null $is_economy Если склад работает с эконом-товарами
 * @property string|null $is_presorted
 * @property int $total
 * @property-read MarketplaceApiKey|null $token
 * @method static Builder<static>|FBSStocks newModelQuery()
 * @method static Builder<static>|FBSStocks newQuery()
 * @method static Builder<static>|FBSStocks query()
 * @method static Builder<static>|FBSStocks whereActive($value)
 * @method static Builder<static>|FBSStocks whereCanPrintActInAdvance($value)
 * @method static Builder<static>|FBSStocks whereDropoffPointId($value)
 * @method static Builder<static>|FBSStocks whereDropoffTimeslotId($value)
 * @method static Builder<static>|FBSStocks whereFirstMileIsChanging($value)
 * @method static Builder<static>|FBSStocks whereFirstMileType($value)
 * @method static Builder<static>|FBSStocks whereHasEntrustedAcceptance($value)
 * @method static Builder<static>|FBSStocks whereHasPostingsLimit($value)
 * @method static Builder<static>|FBSStocks whereId($value)
 * @method static Builder<static>|FBSStocks whereIsAbleToSetPrice($value)
 * @method static Builder<static>|FBSStocks whereIsEconomy($value)
 * @method static Builder<static>|FBSStocks whereIsKarantin($value)
 * @method static Builder<static>|FBSStocks whereIsKgt($value)
 * @method static Builder<static>|FBSStocks whereIsPresorted($value)
 * @method static Builder<static>|FBSStocks whereIsRfbs($value)
 * @method static Builder<static>|FBSStocks whereIsTimetableEditable($value)
 * @method static Builder<static>|FBSStocks whereLastRequest($value)
 * @method static Builder<static>|FBSStocks whereMinPostingsLimit($value)
 * @method static Builder<static>|FBSStocks whereMinWorkingDays($value)
 * @method static Builder<static>|FBSStocks whereName($value)
 * @method static Builder<static>|FBSStocks wherePostingsLimit($value)
 * @method static Builder<static>|FBSStocks whereStatus($value)
 * @method static Builder<static>|FBSStocks whereTid($value)
 * @method static Builder<static>|FBSStocks whereTotal($value)
 * @method static Builder<static>|FBSStocks whereWorkingDays($value)
 * @mixin Builder
 */
class FBSStocks extends Model
{
    use HasFactory, CustomQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'ozon_fbs_stocks';
    protected $fillable = [
        'tid',
        'last_requst',
        'active',
        'name',
        'is_rfbs',
        'is_able_to_set_price',
        'has_entrusted_acceptance',
        'dropoff_point_id',
        'dropoff_timeslot_id',
        'first_mile_is_changing',
        'first_mile_type',
        'is_kgt',
        'can_print_act_in_advance',
        'min_working_days',
        'is_karantin',
        'has_postings_limit',
        'postings_limit',
        'working_days',
        'min_postings_limit',
        'is_timetable_editable',
        'status',
        'is_economy',
        'is_presorted',
        'total'
    ];

    public function token(): BelongsTo
    {
        return $this->belongsTo(MarketplaceApiKey::class, 'tid', 'id');
    }

    /*public function skus(): HasMany
    {
        return $this->hasMany(FBOAmounts::class, 'sid', 'id');
    }*/
}
