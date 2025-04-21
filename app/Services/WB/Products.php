<?php namespace App\Services\WB;

use App\Helpers\Arr;
use App\Helpers\Time;
use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\Schedule;
use App\Services\Sources\Tokens;
use App\Models\Dev\WB\{Products as ModelProducts, PV, Settings, Sizes};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Products extends ProductAbstract
{
    private array $cursors = [];

    private function remains(string $state): int
    {
        return count(array_filter($this->cursors[$state] ?? [], fn($v) => $v !== false));
    }

    protected function start(): void
    {
        if($this->operation->counter === 1)
        {
            foreach([ModelProducts::class, Sizes::class] as $class) /** @var Model $class */ $this->updateInstances($class::query());

            $this->updateInstances(PV::query()->whereNotIn('pid', Settings::query()->whereLike('variable', '%pid')->pluck('value')->all() ?? []));
        }

        foreach($this->cursors = Cache::get($this->hash) ?? [] as $operator => $cursors) foreach ($cursors as $tid => $cursor) if($cursor === false) $this->skip[$operator][$tid] = true;
    }

    protected function collect(): void
    {
        foreach($this->endpoint(Tokens::class) as [$operator, $endpoint, $post])
        {
            $limit = match($this->remains($operator))
            {
                1, 2 => 100, 3 => 75, 4, 5 => 50, default => 30
            };

            foreach($this->manager->source->all('WB') as $tid => $token)
            {
                if(Arr::get($this->skip, $operator, $tid)) continue; $post['settings']['cursor'] = compact('limit') + ($this->cursors[$operator][$tid] ?? []);

                $this->manager->enqueue($endpoint, $token, 'post', $post, $operator, $tid, fn(array $result): array => $this->controller($result, $operator, $tid));
            }
        }
    }

    private function controller(array $result, $operator, $tid): array
    {
        if(!$this->cursors[$operator][$tid] = count($result['cards']) ? $result['cursor'] : false) $this->skip[$operator][$tid] = true; return $result['cards'];
    }

    protected function finish(): bool
    {
        if(array_sum(array_map([$this, 'remains'], array_keys($this->cursors))))
        {
            Log::channel('wb')->info(implode(' | ', ['WB Products Iteration', $this->operation->counter, Time::during(time() - $this->start)])); Cache::set($this->hash, $this->cursors, 1800); return false;
        }

        MarketplaceApiKey::query()->where('marketplace', 'WB')->update(['active' => 'Y']); Cache::delete($this->hash);

        Schedule::shortUpsert([
            ['market' => 'WB', 'operation' => 'PRODUCTS', 'next_start' => strtotime('tomorrow 3:00'), 'counter' => 0],
            ['market' => 'WB', 'operation' => 'FBS_STOCKS', 'next_start' => time(), 'counter' => 0]
        ]);

        return true;
    }
}
