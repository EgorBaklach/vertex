<?php namespace App\Services\YM;

use App\Exceptions\Http\ErrorException;
use App\Exceptions\Http\HttpAbstract;
use App\Helpers\Time;
use App\Models\Dev\Traits\CustomQueries;
use App\Services\APIManager;
use App\Services\Sources\Tokens;
use App\Services\YM\Traits\RecommendationFields;
use App\Models\Dev\YM\{Categories,
    CP,
    PU,
    Properties as ModelProperties,
    PV,
    Recommendations,
    Restrictions,
    Units};
use App\Services\MSAbstract;
use App\Services\Traits\Queries;
use ErrorException as NativeErrorException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class Properties extends MSAbstract
{
    use Queries, RecommendationFields;

    private int $last_id;

    /** @var int[] */
    private array $idRestrictions = [];

    protected int $counter = -1;

    private bool $skip = false;

    private const limit = 2;

    private const classes = [
        ModelProperties::class => 'update',
        Units::class => 'update',
        PV::class => 'update',

        CP::class => 'truncate',
        PU::class => 'truncate',
        Restrictions::class => 'truncate',

        Recommendations::class => 'skip'
    ];

    public function __invoke(): void
    {
        /** @var string|Model|CustomQueries $class */ $start = time(); $manager = $this->endpoint(Tokens::class, APIManager::class);

        $manager->source->handlers['next'] = fn() => $manager->source->next('YM') ?: $this->commit($manager);

        $manager->source->throw = function(Throwable $e, $attributes, ...$data) use ($manager)
        {
            Log::channel('error')->error(implode(' | ', ['YM Properties', (string) $e])); $manager->source->enqueue(...array_values($attributes), ...$data); $attributes['token']->inset('abort');
        };

        if($this->operation->counter === 1)
        {
            foreach(self::classes as $class => $method) match ($method)
            {
                'update' => $this->updateInstances($class::query()), 'truncate' => $class::query()->truncate(), default => null
            };

            Recommendations::query()->where('product_offer_id', 0)->delete();
        }

        foreach(Categories::query()->where('childs', 0)->where('id', '>', $prev_last_id = Cache::get($this->hash) ?? 0)->orderBy('id')->limit(count($manager->source->all('YM')) * 200)->pluck('id')->all() as $cid)
        {
            if($this->skip) break; $this->endpoint(Tokens::class, ++$this->counter && $this->counter % self::limit === 0 ? 'next' : 'current', $cid);
        }

        while($manager->count()) $this->commit($manager);

        if(count($this->results))
        {
            foreach(array_keys($this->idRestrictions) as $pid) $this->results[ModelProperties::class][$pid]['hasValueRestrictions'] = 'Y';

            foreach(array_keys(self::classes) as $class) foreach(array_chunk($this->results[$class] ?? [], 2000) as $chunk) $class::shortUpsert($chunk);

            if($this->skip)
            {
                Log::channel('ym')->info(implode(' | ', ['YM Properties Iteration', $this->operation->counter, $prev_last_id, $this->last_id, count($this->results[ModelProperties::class] ?? []), Time::during(time() - $start)]));

                Cache::set($this->hash, $this->last_id, 300); return;
            }
        }

        Cache::delete($this->hash);

        Log::channel('ym')->info(implode(' | ', ['RESULT', Time::during(time() - $start)]));
        Log::channel('ym')->info(implode(' | ', ['YM Properties', ...Arr::map($this->counts(ModelProperties::class), fn($v, $k) => $k.': '.$v)]));
        Log::channel('ym')->info(implode(' | ', ['YM Units', ...Arr::map($this->counts(Units::class), fn($v, $k) => $k.': '.$v)]));
        Log::channel('ym')->info(implode(' | ', ['YM PV', ...Arr::map($this->counts(PV::class), fn($v, $k) => $k.': '.$v)]));

        $this->operation->update(['next_start' => null, 'counter' => 0]);
    }

    private function commit(APIManager $manager)
    {
        $manager->source->reset('YM');

        $manager->init(function(Response $response)
        {
            if($this->skip) throw new ErrorException($response);

            try
            {
                ['categoryId' => $cid, 'parameters' => $properties] = $response->json('result');

                foreach($properties as $property)
                {
                    // START

                    $hash = implode('_', [$cid, $property['id']]);

                    // PROPERTIES

                    $this->results[ModelProperties::class][$property['id']] ??= [
                        'id' => $property['id'],
                        'last_request' => date('Y-m-d H:i:s'),
                        'active' => 'Y',
                        'type' => $property['type'],
                        'required' => $property['required'] ? 'Y' : null,
                        'filtering' => $property['filtering'] ? 'Y' : null,
                        'distinctive' => $property['distinctive'] ? 'Y' : null,
                        'multivalue' => $property['multivalue'] ? 'Y' : null,
                        'allowCustomValues' => $property['allowCustomValues'] ? 'Y' : null,
                        'hasValues' => count($property['values'] ?? []) ? 'Y' : null,
                        'hasValueRestrictions' => null
                    ];

                    if(count($restrictions = $property['valueRestrictions'] ?? [])) $this->idRestrictions[$property['id']] = true;

                    foreach($restrictions as $restriction)
                    {
                        $this->idRestrictions[$restriction['limitingParameterId']] = true;

                        foreach($restriction['limitedValues'] as $value) foreach($value['optionValueIds'] as $id) $this->results[Restrictions::class][implode('_', [$value['limitingOptionValueId'], $id])] = [
                            'mppvid' => $value['limitingOptionValueId'],
                            'sppvid' => $id
                        ];
                    }

                    // CP

                    $this->results[CP::class][$hash] ??= [
                        'cid' => $cid,
                        'pid' => $property['id'],
                        'name' => $property['name'],
                        'description' => strlen($property['description'] ?? '') ? $property['description'] : null,
                        'constMaxLength' => $property['constraints']['maxLength'] ?? null,
                        'constMinValue' => $property['constraints']['minValue'] ?? null,
                        'constMaxValue' => $property['constraints']['maxValue'] ?? null,
                    ];

                    // UNITS

                    foreach($property['unit']['units'] ?? [] as $unit)
                    {
                        $this->results[Units::class][$unit['id']] ??= [
                            'id' => $unit['id'],
                            'last_request' => date('Y-m-d H:i:s'),
                            'active' => 'Y',
                            'name' => $unit['name'],
                            'fullName' => $unit['fullName']
                        ];

                        $this->results[PU::class][implode('_', [$property['id'], $unit['id']])] = [
                            'pid' => $property['id'],
                            'uid' => $unit['id'],
                            'def' => $unit['id'] === $property['unit']['defaultUnitId'] ? 'Y' : null
                        ];
                    };

                    // RECOMMENDATIONS

                    if(count($property['recommendationTypes'] ?? []))
                    {
                        $this->results[Recommendations::class][$hash] = ['category_id' => $cid, 'property_id' => $property['id']];

                        foreach(array_keys(self::recFields) as $field) $this->results[Recommendations::class][$hash][$field] = in_array($field, $property['recommendationTypes']) ? 100 << 7 : null;
                    }

                    // VALUES

                    foreach($property['values'] ?? [] as $value)
                    {
                        $this->results[PV::class][$value['id']] = [
                            'id' => $value['id'],
                            'last_request' => date('Y-m-d H:i:s'),
                            'active' => 'Y',
                            'pid' => $property['id'],
                            'value' => $value['value'],
                            'description' => strlen($value['description'] ?? '') ? $value['description'] : null
                        ];
                    }
                }

                $this->last_id = $cid;
            }
            catch (Throwable $e)
            {
                throw ($this->skip = $response->status() === 420) ? new ErrorException($response) : new $e;
            }
        });

        $this->counter = 0; return !$this->skip ? $manager->source->current('YM') : false;
    }
}
