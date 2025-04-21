<?php namespace App\Services\OZON;

use App\Helpers\Time;
use App\Models\Dev\Logs;
use App\Models\Dev\OZON\Properties;
use App\Models\Dev\OZON\PV;
use App\Services\Traits\Queries;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use SplQueue;

class Dictionaries extends TokensAbstract
{
    use Queries;

    private int $step = 0;

    private SplQueue $queue;

    protected function collect(): Collection
    {
        if($this->operation->counter === 1) $this->updateInstances(PV::query()); $this->queue = new SplQueue;

        return Properties::query()->whereNotNull('did')->groupBy('did')->get();
    }

    protected function pull(Response $response, array $attributes, callable $callback): void
    {
        ['result' => $values, 'has_next' => $has_next] = $response->json(); /** @var Properties $property */ $property = $callback(fn(Properties $property) => $property); $last_id = 0;

        foreach($values as $value)
        {
            $this->results[implode(':', [$last_id = $value['id'], $property->id])] ??= [
                'id' => $value['id'],
                'did' => $property->did,
                'last_request' => date('Y-m-d H:i:s'),
                'active' => 'Y',
                'value' => $value['value'],
                'info' => strlen($value['info']) ? $value['info'] : null,
                'picture' => strlen($value['picture']) ? $value['picture'] : null
            ];
        }

        if($has_next) $this->queue->enqueue([$property, $last_id]);
    }

    protected function commitAfter(): void
    {
        foreach(array_chunk($this->results, 5000) as $chunk) PV::shortUpsert($chunk); $this->results = []; Logs::query()->where('entity', 'ozon_pv')->delete();

        while(!$this->queue->isEmpty()) $this->enqueue(...$this->queue->dequeue());
    }

    protected function finish(): void
    {
        Log::channel('ozon')->info(implode(' | ', ['RESULT', Time::during(time() - $this->start)]));
        Log::channel('ozon')->info(implode(' | ', ['OZON Property Values', ...Arr::map($this->counts(PV::class), fn($v, $k) => $k.': '.$v)]));

        $this->operation->update(['next_start' => null, 'counter' => 0]);
    }
}
