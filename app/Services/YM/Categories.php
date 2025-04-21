<?php namespace App\Services\YM;

use App\Exceptions\Http\ErrorException;
use App\Helpers\Time;
use App\Models\Dev\Schedule;
use App\Models\Dev\YM\Categories as ModelCategories;
use App\Services\MSAbstract;
use App\Services\Sources\Tokens;
use App\Services\Traits\Queries;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class Categories extends MSAbstract
{
    use Queries;

    private function recursive(array $section, int $pid = null, int $level = 1): void
    {
        $this->results[$section['id']] = [
            'id' => $section['id'],
            'last_request' => date('Y-m-d H:i:s'),
            'active' => 'Y',
            'name' => $section['name'],
            'pid' => $pid,
            'level' => $level++,
            'childs' => 0
        ];

        while(array_key_exists($pid, $this->results)) [$this->results[$pid]['childs']++, $pid = $this->results[$pid]['pid']];

        foreach($section['children'] ?? [] as $child) $this->recursive($child, $section['id'], $level);
    }

    public function __invoke(): void
    {
        $start = time(); if($this->operation->counter === 1) $this->updateInstances(ModelCategories::query()); ModelCategories::query()->update(['childs' => 0]);

        $this->endpoint(Tokens::class)->init(function(Response $response)
        {
            try
            {
                foreach($response->json('result.children') as $parent) $this->recursive($parent);
            }
            catch (Throwable $e)
            {
                Log::channel('error')->error(implode(' | ', ['YM Categories', $response->status(), $response->body(), (string) $e])); throw new ErrorException($response);
            }
        });

        if(!count($this->results)) return; foreach(array_chunk($this->results, 2000) as $chunk) ModelCategories::shortUpsert($chunk);

        Log::channel('ym')->info(implode(' | ', ['RESULT', Time::during(time() - $start)]));
        Log::channel('ym')->info(implode(' | ', ['YM Categories', ...Arr::map($this->counts(ModelCategories::class), fn($v, $k) => $k.': '.$v)]));

        Schedule::shortUpsert([
            ['market' => 'YM', 'operation' => 'CATEGORIES', 'next_start' => strtotime('+3 days midnight'), 'counter' => 0],
            ['market' => 'YM', 'operation' => 'PROPERTIES', 'next_start' => time(), 'counter' => 0]
        ]);
    }
}
