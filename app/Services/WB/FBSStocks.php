<?php namespace App\Services\WB;

use App\Exceptions\Http\ErrorException;
use App\Helpers\Time;
use App\Models\Dev\Schedule;
use App\Models\Dev\WB\FBSAmounts;
use App\Models\Dev\WB\FBSOffices;
use App\Models\Dev\WB\FBSStocks as ModelFBSStocks;
use App\Models\Dev\WB\Sizes;
use App\Services\APIManager;
use App\Services\MSAbstract;
use App\Services\Sources\Tokens;
use App\Services\Traits\Queries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class FBSStocks extends MSAbstract
{
    use Queries;

    private int $last_id = 0;

    private bool $skip = false;

    private const unique = [
        FBSOffices::class => [],
        ModelFBSStocks::class => ['total']
    ];

    private int $repeat = 50;

    private const limit = 10;

    private function build(string $class, string|int $key, ...$values): array
    {
        $result = []; foreach($this->query($class, $values)->get() as $node) $result[$node->{$key}] = $node; return $result;
    }

    private function query(string|Model $class, $methods): Builder
    {
        $builder = $class::query()->where('active', 'Y'); foreach($methods as [$method, $params]) $builder = $builder->{$method}(...(array) $params);

        return match ($class)
        {
            Sizes::class => $builder->orderBy('chrtID')->limit(1000),
            ModelFBSStocks::class => $builder->orderBy('id')
        };
    }

    private function iterate(APIManager $manager): void
    {
        $manager->init(function(Response $response, $attributes, ModelFBSStocks $stock, int $last_id)
        {
            if($this->skip) throw new ErrorException($response);

            try
            {
                foreach($response->json('stocks') as $node)
                {
                    $this->results[FBSAmounts::class][$node['sku']] = $node + ['sid' => $stock->id]; $this->history('history/wb/'.$node['sku'].'/fbs.csv', ' | '.$stock->name.': '.$node['amount']);
                }
            }
            catch (Throwable $e)
            {
                usleep(1000000); throw ($this->skip = --$this->repeat <= 0) ? new ErrorException($response) : $e;
            }

            $this->last_id = $last_id;
        });

        if(array_key_exists(FBSAmounts::class, $this->results)) FBSAmounts::shortUpsert($this->results[FBSAmounts::class]); $this->results = [];
    }

    public function __invoke(): void
    {
        /** @var Model $class */ $start = time();

        //////////////////
        /// GET STOCKS ///
        //////////////////

        if($this->operation->counter === 1)
        {
            foreach([FBSOffices::class, ModelFBSStocks::class] as $class) $this->updateInstances($class::query()); ModelFBSStocks::query()->update(['total' => 0]);

            $this->endpoint(Tokens::class, 'stocks', 'offices')->init(function(Response $response)
            {
                if(!$offices = $response->json()) throw new ErrorException($response);

                foreach($offices as $office) $this->results[FBSOffices::class][$office['id']] = [
                    'id' => $office['id'],
                    'last_request' => date('Y-m-d H:i:s'),
                    'active' => 'Y',
                    'address' => $office['address'],
                    'name' => $office['name'],
                    'city' => $office['city'],
                    'longitude' => $office['longitude'],
                    'latitude' => $office['latitude'],
                    'cargoType' => $office['cargoType'],
                    'deliveryType' => $office['deliveryType'],
                    'selected' => $office['selected'] ? 'Y' : null
                ];
            });

            $this->endpoint(Tokens::class, 'stocks', 'stocks')->init(function(Response $response, $attributes)
            {
                if(!$stocks = $response->json()) throw new ErrorException($response);

                foreach($stocks as $stock) $this->results[ModelFBSStocks::class][$stock['id']] = [
                    'id' => $stock['id'],
                    'last_request' => date('Y-m-d H:i:s'),
                    'active' => 'Y',
                    'officeId' => $stock['officeId'],
                    'tid' => $attributes['token']->id,
                    'name' => $stock['name'],
                    'cargoType' => $stock['cargoType'],
                    'deliveryType' => $stock['deliveryType'],
                ];
            });

            foreach($this->results as $class => $values) $class::upsert($values, self::unique[$class]); $this->results = [];

            Log::channel('wb')->info(implode(' | ', ['WB FBSOffices', ...Arr::map($this->counts(FBSOffices::class), fn($v, $k) => $k.': '.$v)]));
            Log::channel('wb')->info(implode(' | ', ['WB FBSStocks', ...Arr::map($this->counts(ModelFBSStocks::class), fn($v, $k) => $k.': '.$v)]));
        }

        /////////////////////
        /// PARSE AMOUNTS ///
        /////////////////////

        [$last_sid, $this->last_id] = Cache::get($this->hash) ?? [0, 0]; $manager = $this->endpoint(Tokens::class, APIManager::class); $hashes = [$this->hash]; $cur_sid = 0;

        foreach($this->build(ModelFBSStocks::class, 'id', ['where', ['id', '>', $last_sid]]) as $stock)
        {
            /** @var ModelFBSStocks $stock */ $last_id = $this->last_id; $cur_sid = $stock->id.':'.$stock->tid;

            while(true)
            {
                if(!count($skus = Cache::remember($hashes[] = implode('_', ['fbs', $stock->tid, $last_id]), 300, fn() => $this->build(Sizes::class, 'sku', ['where', ['tid', $stock->tid]], ['where', ['chrtID', '>', $last_id]])))) break;

                $this->endpoint(Tokens::class, 'amounts', $stock, array_map('strval', array_keys($skus)), $last_id = call_user_func(fn(Sizes $size): int => $size->chrtID, array_pop($skus)));

                if($manager->count() >= self::limit) $this->iterate($manager); if($this->skip) break 2;
            }

            if($manager->count()) $this->iterate($manager); if($this->skip) break; $last_sid = $stock->id; $this->last_id = 0;
        }

        if($this->skip)
        {
            Log::channel('wb')->info(implode(' | ', ['WB FBSStocks Iteration', $this->operation->counter, json_encode(compact('last_sid', 'cur_sid') + ['last_id' => $this->last_id]), Time::during(time() - $start)]));

            Cache::set($this->hash, [$last_sid, $this->last_id], 300); return;
        }

        foreach(FBSAmounts::query()->groupBy('sid')->get(['sid', DB::raw('count(*) as cnt')]) as $value) $this->results[$value->sid] = ['id' => $value->sid, 'total' => $value->cnt];

        ModelFBSStocks::shortUpsert($this->results); foreach($hashes as $hash) Cache::delete($hash);

        Log::channel('wb')->info(implode(' | ', ['WB FBSAmounts', array_sum(array_column($this->results, 'total')), Time::during(time() - $start)]));

        Schedule::shortUpsert([
            ['market' => 'WB', 'operation' => 'FBS_STOCKS', 'next_start' => null, 'counter' => 0],
            ['market' => 'WB', 'operation' => 'PRICES', 'next_start' => time(), 'counter' => 0]
        ]);
    }
}
