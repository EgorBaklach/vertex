<?php namespace App\Services\WB;

use App\Helpers\Func;
use App\Exceptions\Http\{ErrorException, RepeatException};
use App\Services\APIManager;
use App\Services\Sources\Tokens;
use App\Helpers\Time;
use App\Models\Dev\Logs;
use App\Models\Dev\Schedule;
use App\Models\Dev\WB\{Categories, CP, Properties as ModelProperties, PV, Settings};
use App\Services\MSAbstract;
use App\Services\Traits\Queries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Properties extends MSAbstract
{
    use Queries;

    private array $tnveds = [];

    private array $ids = [];

    private const additionals = [
        'colors' => 'Цвет',
        'kinds' => 'Пол',
        'countries' => 'Страна производства',
        'seasons' => 'Сезон',
        'vat' => 'Ставка НДС',
        'tnved' => 'ТНВЭД'
    ];

    private function properties(array $property): void
    {
        foreach(self::additionals as $field => $name)
        {
            if($property['name'] === $name)
            {
                $this->results[Settings::class][$field] ??= [
                    'variable' => implode(':', [$field, 'pid']),
                    'last_request' => date('Y-m-d H:i:s'),
                    'value' => $property['charcID']
                ];
            }
        }

        $this->results[ModelProperties::class][$property['charcID']] ??= [
            'id' => $property['charcID'],
            'last_request' => date('Y-m-d H:i:s'),
            'active' => 'Y',
            'name' => $property['name'],
            'required' => $property['required'] ? 'Y' : null,
            'unit' => strlen($property['unitName']) ? $property['unitName'] : null,
            'count' => $property['maxCount'],
            'popular' => $property['popular'] ? 'Y' : null,
            'type' => $property['charcType']
        ];

        $this->results[CP::class][implode(':', [$property['subjectID'], $property['charcID']])] ??= [
            'cid' => $property['subjectID'],
            'pid' => $property['charcID']
        ];
    }

    private function tnved(array $tnved, int $cid): void
    {
        $this->tnveds[] = [$cid, ...array_values($tnved)];
    }

    public function __invoke(): void
    {
        $start = time(); $manager = $this->endpoint(Tokens::class, APIManager::class); $last_id = 0;

        $manager->source->handlers['next'] = fn(string $market) => Func::call($manager->source, fn(Tokens $source) => $source->next($market) ?: $source->reset($market, 500000)->current($market));

        if($this->operation->counter === 1) $this->updateInstances(PV::query()->whereIn('pid', Settings::whereLike('variable', '%pid')->pluck('value')->all() ?? []));

        $tvend_pid = Cache::remember('wb_tvend_pid', 3600, fn() => (Settings::whereLike('variable', 'tnved:pid')->pluck('value')->first() * 1) ?: 15000001);

        while(true)
        {
            foreach(Categories::query()->where('id', '>', $last_id)->orderBy('id')->limit(5)->pluck('id') as $id) $this->endpoint(Tokens::class, $id); if(!$manager->count()) break;

            $manager->init(function(Response $response, $attributes, string $operation, int $cid)
            {
                if(!is_array($values = $response->json('data'))) throw new ($response->status() === 429 ? RepeatException::class : ErrorException::class)($response);

                foreach($values as $value) $this->{$operation}($value, $cid); $this->ids[$cid][] = $operation;
            });

            foreach($this->tnveds as [$cid, $tnved, $isKiz]) $this->results[PV::class][] = [
                'last_request' => date('Y-m-d H:i:s'),
                'active' => 'Y',
                'pid' => $this->results[Settings::class]['tnved']['value'] ?? $tvend_pid,
                'value' => $tnved,
                'bind' => $cid,
                'params' => $isKiz ? 'Y' : null
            ];

            foreach($this->results as $class => $values) /** @var Builder|string $class */ $class::upsert($values, []); if(array_key_exists(PV::class, $this->results)) Logs::query()->where('entity', 'wb_pv')->delete();

            ksort($this->ids); foreach($this->ids as $id => $operations) if(!count(array_diff(['properties', 'tnved'], $operations))) $last_id = $id * 1; $this->ids = $this->results = $this->tnveds = [];
        }

        Log::channel('wb')->info(implode(' | ', ['RESULT', Time::during(time() - $start)]));
        Log::channel('wb')->info(implode(' | ', ['WB Properties', ...Arr::map($this->counts(ModelProperties::class), fn($v, $k) => $k.': '.$v)]));

        Schedule::shortUpsert([
            ['market' => 'WB', 'operation' => 'PROPERTIES', 'next_start' => null, 'counter' => 0],
            ['market' => 'WB', 'operation' => 'DIRECTORIES', 'next_start' => time(), 'counter' => 0]
        ]);
    }
}
