<?php namespace App\Services\WB;

use App\Helpers\Func;
use App\Services\APIManager;
use App\Services\Sources\Tokens;
use App\Exceptions\Http\{ErrorException, RepeatException};
use App\Helpers\Time;
use App\Models\Dev\Schedule;
use App\Models\Dev\WB\{Categories as ModelCategories, Properties};
use App\Services\MSAbstract;
use App\Services\Traits\Queries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Categories extends MSAbstract
{
    use Queries;

    private int $last_id = 0;

    public function __invoke(): void
    {
        $start = time(); $manager = $this->endpoint(Tokens::class, APIManager::class); $this->last_id = Cache::get($this->hash) ?: 0;

        $manager->source->handlers['next'] = fn(string $market) => Func::call($manager->source, fn(Tokens $source) => $source->next($market) ?: $source->reset($market)->current($market));

        if($this->operation->counter === 1)
        {
            foreach([ModelCategories::class, Properties::class] as $class) /** @var Model $class */ $this->updateInstances($class::query());

            $this->endpoint(Tokens::class, 'parent')->init(function (Response $response)
            {
                foreach($response->json('data') as $section) $this->results[] = ['id' => $section['id'], 'active' => 'Y', 'last_request' => date('Y-m-d H:i:s'), 'name' => $section['name']];
            });

            if(count($this->results)) ModelCategories::upsert($this->results, [], ['last_request', 'active', 'name']); $this->results = [];
        }

        while(true)
        {
            $cids = ModelCategories::query()->whereLike('parentId', 0)->where('id', '>', $this->last_id)->limit(5)->orderBy('id')->pluck('id');

            if(!$cids->count()): $this->last_id = 0; break; endif; foreach($cids as $id) $this->endpoint(Tokens::class, 'children', $id); if(!$manager->count()) break;

            $manager->init(function(Response $response, $attributes, $cid)
            {
                if(!is_array($categories = $response->json('data'))) throw new ($response->status() === 429 ? RepeatException::class : ErrorException::class)($response); $counter = 0; $this->last_id = $cid;

                foreach($categories as $section)
                {
                    $this->results[$section['subjectID']] = [
                        'id' => $section['subjectID'],
                        'last_request' => date('Y-m-d H:i:s'),
                        'active' => 'Y',
                        'childs' => 0,
                        'name' => $section['subjectName'],
                        'parentId' => $section['parentID']
                    ];

                    $this->results[$section['parentID']] = [
                        'id' => $section['parentID'],
                        'last_request' => date('Y-m-d H:i:s'),
                        'active' => 'Y',
                        'childs' => ++$counter,
                        'name' => $section['parentName'],
                        'parentId' => 0
                    ];
                }
            });

            if(count($this->results)) ModelCategories::upsert($this->results, ['parentId'], ['last_request', 'active', 'childs', 'name']); $this->results = []; usleep(1);
        }

        if($this->last_id)
        {
            Cache::set($this->hash, $this->last_id, 300); Log::channel('wb')->info(implode(' | ', ['WB Categories Iteration', $this->operation->counter, Time::during(time() - $start)])); return;
        }

        Log::channel('wb')->info(implode(' | ', ['RESULT', Time::during(time() - $start)]));
        Log::channel('wb')->info(implode(' | ', ['WB Categories', ...Arr::map($this->counts(ModelCategories::class), fn($v, $k) => $k.': '.$v)]));

        Schedule::shortUpsert([
            ['market' => 'WB', 'operation' => 'CATEGORIES', 'next_start' => strtotime('+3 days midnight'), 'counter' => 0],
            ['market' => 'WB', 'operation' => 'PROPERTIES', 'next_start' => time(), 'counter' => 0]
        ]);

        Cache::delete($this->hash);
    }
}
