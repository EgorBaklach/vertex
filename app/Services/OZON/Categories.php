<?php namespace App\Services\OZON;

use App\Helpers\Func;
use App\Helpers\Time;
use App\Models\Dev\OZON\{Categories as ModelCategories, CT, Types};
use App\Models\Dev\Schedule;
use App\Services\MSAbstract;
use App\Services\Sources\Tokens;
use App\Services\Traits\Queries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Categories extends MSAbstract
{
    use Queries;

    private int $counter = 0;

    private const limit = 1000;

    private function recursive(array $nodes, int $level = 0, int $cid = 0): void
    {
        if($this->counter >= self::limit) $this->iterate(); $level++;

        foreach($nodes as $node)
        {
            match(true)
            {
                boolval($node['description_category_id'] ?? false) => $this->results[ModelCategories::class][$node['description_category_id']] = [
                    'id' => $node['description_category_id'],
                    'last_request' => date('Y-m-d H:i:s'),
                    'active' => 'Y',
                    'disabled' => $node['disabled'] ? 'Y' : null,
                    'name' => $node['category_name'],
                    'parent_id' => $cid,
                    'level' => $level
                ],
                boolval($node['type_id'] ?? false) => Func::call($node['type_id'], fn(int $tid): array => [
                    $this->results[CT::class][implode(':', [$cid, $tid])] = compact('cid', 'tid'),
                    $this->results[Types::class][$tid] ??= [
                        'id' => $tid,
                        'last_request' => date('Y-m-d H:i:s'),
                        'active' => 'Y',
                        'disabled' => $node['disabled'] ? 'Y' : null,
                        'name' => $node['type_name']
                    ]
                ])
            };

            if($cid): $this->results['update'][$cid] ??= ['id' => $cid, 'childs' => 0]; $this->results['update'][$cid]['childs']++; endif;

            $this->counter++; if(count($node['children'])) $this->recursive($node['children'], $level, intval($node['description_category_id'] ?? 0));
        }
    }

    public function __invoke(): void
    {
        $start = time(); foreach([ModelCategories::class, Types::class] as $class) /** @var Model $class */ $this->updateInstances($class::query()); ModelCategories::query()->update(['childs' => 0]);

        $this->endpoint(Tokens::class)->init(fn(Response $response) => $this->recursive($response->json('result'))); if($this->counter) $this->iterate();

        Log::channel('ozon')->info(implode(' | ', ['RESULT', Time::during(time() - $start)]));
        Log::channel('ozon')->info(implode(' | ', ['OZON Categories', ...Arr::map($this->counts(ModelCategories::class), fn($v, $k) => $k.': '.$v)]));
        Log::channel('ozon')->info(implode(' | ', ['OZON Types', ...Arr::map($this->counts(Types::class), fn($v, $k) => $k.': '.$v)]));

        Schedule::shortUpsert([
            ['market' => 'OZON', 'operation' => 'CATEGORIES', 'next_start' => strtotime('+3 days midnight'), 'counter' => 0],
            ['market' => 'OZON', 'operation' => 'PROPERTIES', 'next_start' => time(), 'counter' => 0]
        ]);
    }

    private function iterate(): void
    {
        foreach([ModelCategories::class, 'update', Types::class, CT::class] as $key) if(count($values = $this->results[$key] ?? [])) match ($key)
        {
            'update' => ModelCategories::upsert($values, [], ['childs' => DB::raw('`childs` + values(`childs`)')]),
            default => Func::call($key, fn(string|ModelCategories|Types $class) => $class::shortUpsert($values))
        };

        $this->results = []; $this->counter = 0;
    }
}
