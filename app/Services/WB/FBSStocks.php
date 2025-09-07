<?php namespace App\Services\WB;

use App\Exceptions\Http\ErrorException;
use App\Helpers\Time;
use App\Models\Dev\Schedule;
use App\Models\Dev\WB\{FBSAmounts, FBSOffices, FBSStocks as ModelFBSStocks, Sizes};
use App\Services\APIManager;
use App\Services\MSAbstract;
use App\Services\Sources\Tokens;
use Illuminate\Database\Eloquent\Builder;
use App\Services\Traits\Queries;
use ErrorException as NativeErrorException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\{Cache, DB, Log};
use Throwable;

class FBSStocks extends MSAbstract
{
    use Queries;

    /** @var int[] */
    private array $remains = [];

    private int $delay = 0;

    private int $last_id = 0;

    private const unique = [
        FBSOffices::class => [],
        ModelFBSStocks::class => ['total']
    ];

    public function __invoke(): void
    {
        /**
         * @var string|Model $class
         * @var FBSAmounts $amount
         * @var ModelFBSStocks $stock
         * */

        $start = time(); $hashes = []; $manager = $this->endpoint(Tokens::class, APIManager::class);

        //////////////////
        /// GET STOCKS ///
        //////////////////

        if($this->operation->counter === 1)
        {
            foreach([FBSOffices::class, ModelFBSStocks::class] as $class) $this->updateInstances($class::query()); ModelFBSStocks::query()->update(['total' => 0]); FBSAmounts::query()->truncate();

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

            foreach($this->results as $class => $values) $class::query()->upsert($values, match($class)
            {
                ModelFBSStocks::class => ['total'], default => []
            });

            $this->results = [];

            Log::channel('wb')->info(implode(' | ', ['WB FBSOffices', ...Arr::map($this->counts(FBSOffices::class), fn($v, $k) => $k.': '.$v)]));
            Log::channel('wb')->info(implode(' | ', ['WB FBSStocks', ...Arr::map($this->counts(ModelFBSStocks::class), fn($v, $k) => $k.': '.$v)]));
        }

        /////////////////////
        /// PARSE AMOUNTS ///
        /////////////////////

        $manager->source->throw = function(Throwable $e, $attributes, ...$data) use ($manager)
        {
            $manager->enqueue(...array_values($attributes), ...$data); $attributes['token']->inset('abort');
        };

        foreach(ModelFBSStocks::query()->where('active', 'Y')->orderBy('id')->get() as $stock)
        {
            while($this->endpoint(Tokens::class, 'amounts', $stock, $this->extract(...Cache::remember($hashes[] = implode('_', ['fbs', $stock->tid, $this->last_id]), 3600, fn() => $this->inject($stock->tid)))))
            {
                if($manager->count() >= $step ??= 20): $this->request($manager); $step = max(10, ...$this->remains); $this->remains = []; endif;
            }

            if($manager->count()) $this->request($manager); $this->last_id = 0; $step = null;
        }

        foreach(FBSAmounts::query()->groupBy('sid')->get(['sid', DB::raw('count(*) as cnt')]) as $amount) $this->results[$amount->sid] = ['id' => $amount->sid, 'total' => $amount->cnt];

        ModelFBSStocks::shortUpsert($this->results); foreach($hashes as $hash) if(Cache::has($hash)) Cache::delete($hash);

        Log::channel('wb')->info(implode(' | ', ['WB FBSAmounts', array_sum(array_column($this->results, 'total')), Time::during(time() - $start)]));

        Schedule::shortUpsert([
            ['market' => 'WB', 'operation' => 'FBS_STOCKS', 'next_start' => null, 'counter' => 0],
            ['market' => 'WB', 'operation' => 'PRICES', 'next_start' => time(), 'counter' => 0]
        ]);
    }

    private function extract(array $skus, int $last_id): array
    {
        $this->last_id = $last_id; return $skus;
    }

    private function inject(int $tid): array
    {
        /** @var Sizes $size */

        while(count($skus ??= []) < 1000)
        {
            $rs = Sizes::query()->where('active', 'Y')->where('tid', $tid)->where('chrtID', '>', $this->last_id)->limit(500)->orderBy('chrtID')->get(); if(!$rs->count()) break;

            foreach($rs as $size)
            {
                $skus[] = $size->sku; $this->last_id = $size->chrtID;
            }
        }

        return [$skus, $this->last_id];
    }

    private function request(APIManager $manager): void
    {
        $manager->init(function(Response $response, $attributes, ModelFBSStocks $stock)
        {
            try
            {
                if($response->status() >= 400) throw new NativeErrorException('Status Error: '.$response->status());

                $this->remains[] = preg_replace('/[^0-9]+/i', '', $response->getHeaderLine('X-Ratelimit-Remaining')) * 1;

                foreach($response->json('stocks') as $node)
                {
                    $this->results[FBSAmounts::class][$node['sku']] = $node + ['sid' => $stock->id]; $this->history('history/wb/'.$node['sku'].'/fbs.csv', ' | '.$stock->name.': '.$node['amount']);
                }
            }
            catch (Throwable $e)
            {
                $this->delay += preg_replace('/[^0-9]+/i', '', $response->getHeaderLine('X-Ratelimit-Retry')) * 1; throw $e;
            }
        });

        if(array_key_exists(FBSAmounts::class, $this->results)) FBSAmounts::shortUpsert($this->results[FBSAmounts::class]);

        if($this->delay) usleep($this->delay * 500000); $this->delay = 0; $this->results = [];
    }
}
