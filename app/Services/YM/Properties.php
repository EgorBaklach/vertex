<?php namespace App\Services\YM;

use App\Exceptions\Http\ErrorException;
use App\Helpers\Func;
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
        /** @var string|Model|CustomQueries $class */ $start = time(); $last_id = Cache::get($this->hash) ?? 0; $manager = $this->endpoint(Tokens::class, APIManager::class);

        $manager->source->handlers['current'] = fn(string $market) => Func::call($manager->source, fn(Tokens $source) => $source->next($market) ?: $source->reset($market)->current($market));

        if($this->operation->counter === 1)
        {
            foreach(self::classes as $class => $method) match ($method)
            {
                'update' => $this->updateInstances($class::query()), 'truncate' => $class::query()->truncate(), default => null
            };

            Recommendations::query()->where('product_offer_id', 0)->delete();
        }

        foreach(array_chunk(Categories::query()->where('childs', 0)->where('id', '>', $last_id)->orderBy('id')->limit(100)->pluck('id')->all(), 2) as $chunk)
        {
            $this->endpoint(Tokens::class, $chunk)->init(function(Response $response)
            {
                try
                {
                    ['categoryId' => $cid, 'parameters' => $properties] = $response->json('result');

                    foreach($properties as $property)
                    {
                        // TODO DELETE THIS

                        if($property['id'] === 60035061) Log::channel('ym')->info(['YES!!! ITS PROPERTY 60035061', json_encode($property)]);

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
                    Log::channel('error')->error(implode(' | ', ['YM Properties', $response->status(), $response->body(), (string) $e])); throw new ErrorException($response);
                }
            });
        }

        foreach(array_keys($this->idRestrictions) as $pid) $this->results[ModelProperties::class][$pid]['hasValueRestrictions'] = 'Y';

        if(count($this->results))
        {
            foreach(array_keys(self::classes) as $class) foreach(array_chunk($this->results[$class] ?? [], 2000) as $chunk) $class::shortUpsert($chunk);

            Cache::set($this->hash, $this->last_id, 300); Log::channel('ym')->info(implode(' | ', ['YM Properties Iteration', $this->operation->counter, Time::during(time() - $start)])); return;
        }

        Cache::delete($this->hash); $this->operation->update(['next_start' => null, 'counter' => 0]);

        Log::channel('ym')->info(implode(' | ', ['RESULT', Time::during(time() - $start)]));
        Log::channel('ym')->info(implode(' | ', ['YM Properties', ...Arr::map($this->counts(ModelProperties::class), fn($v, $k) => $k.': '.$v)]));
        Log::channel('ym')->info(implode(' | ', ['YM Units', ...Arr::map($this->counts(Units::class), fn($v, $k) => $k.': '.$v)]));
        Log::channel('ym')->info(implode(' | ', ['YM PV', ...Arr::map($this->counts(PV::class), fn($v, $k) => $k.': '.$v)]));
    }
}
