<?php namespace App\Services\WB;

use App\Exceptions\Http\ErrorException;
use App\Helpers\Time;
use App\Models\Dev\Logs;
use App\Models\Dev\WB\{FBOAmounts, FBOStocks as ModelFBOStocks, Sizes};
use App\Services\APIManager;
use App\Services\MSAbstract;
use App\Services\Sources\Tokens;
use App\Services\Traits\Queries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use JsonMachine\Items;

class FBOStocks extends MSAbstract
{
    use Queries;

    private int $counter = 30;

    /** @var int[] */
    private array $sids = [];

    private function remains(): int
    {
        return count(array_filter($this->results ?? [], fn($values) => is_array($values) && $values['status'] !== 'done'));
    }

    private function iterate(): void
    {
        foreach ([ModelFBOStocks::class, FBOAmounts::class, Sizes::class] as $class)
        {
            /** @var string|Model $class */ if(!array_key_exists($class, $this->results)) continue; $values = $this->results[$class];

            match($class)
            {
                ModelFBOStocks::class => call_user_func(function () use ($values)
                {
                    ModelFBOStocks::upsert($values, [], ['last_request', 'active', 'total' => DB::raw('`total` + values(`total`)')]);

                    foreach(Logs::query()->where('entity', 'wb_fbo_stocks')->pluck('value')->map(fn($value) => explode(' | ', $value)) as [$id, $name]) $this->sids[md5($name)] ??= $id * 1;

                    Logs::query()->where('entity', 'wb_fbo_stocks')->delete();
                }),
                FBOAmounts::class => FBOAmounts::shortUpsert(Arr::map($values, fn(array $value) => array_replace($value, ['sid' => $this->sids[$value['sid']]]))),
                Sizes::class => Sizes::shortUpsert($values)
            };
        }

        $this->results = []; usleep(1);
    }

    public function __invoke(): void
    {
        $start = time(); Storage::disk('local')->deleteDirectory('reports');

        ///////////////////
        /// GET REPORTS ///
        ///////////////////

        $this->updateInstances(ModelFBOStocks::query()); ModelFBOStocks::query()->update(['total' => 0]); FBOAmounts::query()->truncate();

        $this->endpoint(Tokens::class, 'create')->init(function(Response $response, $attributes)
        {
            if(!$id = $response->json('data.taskId')) throw new ErrorException($response); $this->results[$attributes['token']->id] = compact('id') + ['status' => 'create'];
        });

        while(true)
        {
            if(!$this->remains() || !--$this->counter) break;

            $this->endpoint(Tokens::class, 'ping', fn(int $tid) => $this->results[$tid]['id'])->init(function(Response $response, $attributes)
            {
                if(!$node = $response->json('data')) throw new ErrorException($response); $this->results[$attributes['token']->id] = $node;
            });

            usleep(1500000);
        }

        if($this->remains()) return;

        $this->endpoint(Tokens::class, 'get', fn(int $tid) => $this->results[$tid]['id'])->init(function(Response $response, $attributes)
        {
            if(!$response->successful()) throw new ErrorException($response); $resource = $response->resource();

            Storage::disk('local')->writeStream('reports/'.$attributes['token']->id.'.json', $resource); fclose($resource);
        });

        /////////////////////
        /// PARSE REPORTS ///
        /////////////////////

        $DB = DB::connection('dev'); $DB->statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach($this->endpoint(Tokens::class, APIManager::class)->source->all('WB') as $token)
        {
            foreach(Items::fromFile(storage_path('app/private/reports/'.$token->id.'.json'), ['decoder' => APP::make('json_decoder')]) as $sku)
            {
                if(!strlen($sku['barcode'] ?? false)) continue; $stocks = [date('Y-m-d H:i:s')];

                foreach($sku['warehouses'] as $stock)
                {
                    $stocks[$stock['warehouseName']] = $stock['quantity'];

                    $this->results[ModelFBOStocks::class][$hash = md5($stock['warehouseName'])] ??= [
                        'last_request' => date('Y-m-d H:i:s'),
                        'active' => 'Y',
                        'name' => $stock['warehouseName'],
                        'total' => 0
                    ];

                    $this->results[ModelFBOStocks::class][$hash]['total'] += $stock['quantity'];

                    $this->results[FBOAmounts::class][$sku['barcode'].' | '.$hash] = [
                        'sku' => $sku['barcode'],
                        'sid' => $hash,
                        'amount' => $stock['quantity']
                    ];
                }

                //Storage::disk('local')->append('history/wb/'.$sku['barcode'].'/fbo.csv', implode(' | ', Arr::map($stocks, fn($v, $k) => is_int($k) ? $v : $k.': '.$v)));

                $this->results[Sizes::class][$token->id.' | '.$sku['barcode']] = [
                    'tid' => $token->id,
                    'last_request' => date('Y-m-d H:i:s'),
                    'active' => 'Y',
                    'sku' => $sku['barcode']
                ];

                if(count($this->results[FBOAmounts::class] ?? []) >= 2000) $this->iterate();
            }

            if(count($this->results)) $this->iterate();
        }

        $DB->statement('SET FOREIGN_KEY_CHECKS=1;');

        Log::channel('wb')->info(implode(' | ', ['RESULTS', Time::during(time() - $start)]));
        Log::channel('wb')->info(implode(' | ', ['WB FBO Stocks', ...Arr::map($this->counts(ModelFBOStocks::class), fn($v, $k) => $k.': '.$v)]));
        Log::channel('wb')->info(implode(' | ', ['WB FBO Amounts', FBOAmounts::query()->count()]));

        $this->operation->update(['next_start' => DB::raw('next_start + 10800'), 'counter' => 0]);
    }
}
