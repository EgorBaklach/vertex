<?php namespace App\Models\Dev;

use App\Models\Dev\OZON\Prices as OZONPrices;
use App\Models\Dev\Traits\SourceQueries;
use App\Models\Dev\WB\Products as WBProducts;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $marketplace
 * @property string|null $active
 * @property string $name
 * @property string $token
 * @property array|null $params
 * @property array $days
 * @property int $process
 * @property int $success
 * @property int $abort
 * @property string|null $last_request
 * @property-read array $encode
 * @method static Builder<static>|MarketplaceApiKey newModelQuery()
 * @method static Builder<static>|MarketplaceApiKey newQuery()
 * @method static Builder<static>|MarketplaceApiKey query()
 * @method static Builder<static>|MarketplaceApiKey whereAbort($value)
 * @method static Builder<static>|MarketplaceApiKey whereActive($value)
 * @method static Builder<static>|MarketplaceApiKey whereId($value)
 * @method static Builder<static>|MarketplaceApiKey whereLastRequest($value)
 * @method static Builder<static>|MarketplaceApiKey whereMarketplace($value)
 * @method static Builder<static>|MarketplaceApiKey whereName($value)
 * @method static Builder<static>|MarketplaceApiKey whereParams($value)
 * @method static Builder<static>|MarketplaceApiKey whereDays($value)
 * @method static Builder<static>|MarketplaceApiKey whereProcess($value)
 * @method static Builder<static>|MarketplaceApiKey whereSuccess($value)
 * @method static Builder<static>|MarketplaceApiKey whereToken($value)
 * @mixin Builder
 */
class MarketplaceApiKey extends Model
{
    use HasFactory, SourceQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'marketplace_api_keys';
    protected $fillable = [
        'marketplace',
        'active',
        'name',
        'token',
        'params',
        'days'
    ];

    protected function casts(): array
    {
        return [
            'params' => 'array',
            'days' => 'array'
        ];
    }

    public function encode(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->marketplace)
            {
                'WB' => ['withToken' => $this->token], default => ['withHeaders' => [$this->params + ['Api-Key' => $this->token]]]
            }
        );
    }

    public function setDaysAttribute($value): void
    {
        sort($value); $this->attributes['days'] = json_encode($value, JSON_NUMERIC_CHECK);
    }

    public function setParamsAttribute($value): void
    {
        $this->attributes['params'] = json_encode($value, JSON_NUMERIC_CHECK);
    }

    public function getDaysStringAttribute(): string
    {
        return count($this->days) ? implode(', ', array_map(fn($day) => ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'][$day -1] ?? $day, $this->days)) : 'Не выбрано';
    }
}
