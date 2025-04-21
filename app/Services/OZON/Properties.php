<?php namespace App\Services\OZON;

use App\Helpers\Time;
use App\Models\Dev\OZON\{CT, CTP, Properties as ModelProperties};
use App\Models\Dev\Schedule;
use App\Services\Traits\Queries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class Properties extends TokensAbstract
{
    use Queries;

    protected function collect(): Collection
    {
        if($this->operation->counter === 1) $this->updateInstances(ModelProperties::query());

        return CT::query()->where(fn(Builder $q) => array_map(fn($r) => $q->whereHas($r, fn(Builder $b) => $b->where('active', 'Y')), ['category', 'type']))->get();
    }

    protected function pull(Response $response, array $attributes, callable $callback): void
    {
        $ct = $callback(fn(int $cid, int $tid) => compact('cid', 'tid'));

        foreach($response->json('result') as $property)
        {
            $this->results[CTP::class][json_encode($value = $ct + ['pid' => $property['id']])] ??= $value;

            $this->results[ModelProperties::class][$property['id']] ??= [
                'id' => $property['id'],
                'last_request' => date('Y-m-d H:i:s'),
                'active' => 'Y',
                'attribute_complex_id' => $property['attribute_complex_id'] ?: null,
                'name' => $property['name'],
                'description' => strlen($property['description']) ? $property['description'] : null,
                'type' => $property['type'],
                'is_collection' => $property['is_collection'] ? 'Y' : null,
                'is_required' => $property['is_required'] ? 'Y' : null,
                'is_aspect' => $property['is_aspect'] ? 'Y' : null,
                'max_value_count' => $property['max_value_count'],
                'group_id' => $property['group_id'] ?: null,
                'group_name' => strlen($property['group_name']) ? $property['group_name'] : null,
                'did' => $property['dictionary_id'] ?: null,
                'category_dependent' => $property['category_dependent'] ? 'Y' : null,
            ];
        }
    }

    protected function commitAfter(): void
    {
        foreach([ModelProperties::class, CTP::class] as $class) /** @var ModelProperties|CTP|string $class */ $class::shortUpsert($this->results[$class] ?? []); $this->results = [];
    }

    protected function finish(): void
    {
        Log::channel('ozon')->info(implode(' | ', ['RESULT', Time::during(time() - $this->start)]));
        Log::channel('ozon')->info(implode(' | ', ['OZON Properties', ...Arr::map($this->counts(ModelProperties::class), fn($v, $k) => $k.': '.$v)]));

        Schedule::shortUpsert([
            ['market' => 'OZON', 'operation' => 'PROPERTIES', 'next_start' => null, 'counter' => 0],
            ['market' => 'OZON', 'operation' => 'DICTIONARIES', 'next_start' => time(), 'counter' => 0]
        ]);
    }
}
