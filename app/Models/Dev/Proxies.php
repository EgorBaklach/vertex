<?php namespace App\Models\Dev;

use App\Models\Dev\Traits\SourceQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $active
 * @property string|null $ip
 * @property int|null $port
 * @property string|null $user
 * @property string|null $pass
 * @property string|null $type
 * @property string|null $country
 * @property string|null $provider
 * @property int $process
 * @property int $success
 * @property int $abort
 * @property string|null $last_request
 * @property string address
 * @method static Builder<static>|Proxies newModelQuery()
 * @method static Builder<static>|Proxies newQuery()
 * @method static Builder<static>|Proxies query()
 * @method static Builder<static>|Proxies whereAbort($value)
 * @method static Builder<static>|Proxies whereActive($value)
 * @method static Builder<static>|Proxies whereCountry($value)
 * @method static Builder<static>|Proxies whereId($value)
 * @method static Builder<static>|Proxies whereIp($value)
 * @method static Builder<static>|Proxies whereLastRequest($value)
 * @method static Builder<static>|Proxies wherePass($value)
 * @method static Builder<static>|Proxies wherePort($value)
 * @method static Builder<static>|Proxies whereProcess($value)
 * @method static Builder<static>|Proxies whereProvider($value)
 * @method static Builder<static>|Proxies whereSuccess($value)
 * @method static Builder<static>|Proxies whereType($value)
 * @method static Builder<static>|Proxies whereUser($value)
 * @mixin Builder
 */
class Proxies extends Model
{
    use HasFactory, SourceQueries;

    public $timestamps = false;

    protected $connection = 'dev';
    protected $table = 'proxies';
    protected $fillable = [
        'active',
        'ip',
        'port',
        'user',
        'pass',
        'type',
        'country',
        'provider'
    ];

    protected function address(): Attribute
    {
        return Attribute::make(
            get: fn () => 'http://'.$this->user.':'.$this->pass.'@'.$this->ip.':'.$this->port,
        );
    }
}
