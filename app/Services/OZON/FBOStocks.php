<?php namespace App\Services\OZON;

use App\Exceptions\Http\ErrorException;
use App\Helpers\Time;
use App\Models\Dev\Logs;
use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\OZON\FBOAmounts;
use App\Models\Dev\OZON\FBOStocks as ModelFBOStocks;
use App\Services\APIManager;
use App\Services\MSAbstract;
use App\Services\Sources\Tokens;
use App\Services\Traits\Queries;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class FBOStocks extends MSAbstract
{
    use Queries;

    /** @var int[] */
    private array $offset = [];

    /** @var int[] */
    private array $sids = [];

    private const limit = 1000;

    public function __invoke(): void
    {
        $start = time(); $manager = $this->endpoint(Tokens::class, APIManager::class); $this->offset = Cache::get($this->hash) ?: []; $DB = DB::connection('dev');

        match($this->operation->counter)
        {
            1 => call_user_func(function()
            {
                $this->updateInstances(ModelFBOStocks::query()); ModelFBOStocks::query()->update(['total' => 0]); FBOAmounts::query()->truncate();
            }),
            default => sleep($this->operation->counter * 10)
        };

        foreach($manager->source->all('OZON') as $tid => $token) if(($offset = Arr::get($this->offset, $tid)) !== false) $this->endpoint(Tokens::class, $token, $offset ?: 0, self::limit);

        $manager->init(function(Response $response, $attributes)
        {
            /** @var MarketplaceApiKey $token */ $token = $attributes['token'];

            try
            {
                $this->offset[$token->id] = ($cnt = count($amounts = $response->json('items'))) > self::limit ? $cnt : false;

                foreach($amounts as $amount)
                {
                    foreach(['valid', 'waitingdocs', 'expiring', 'defect'] as $type)
                    {
                        if(!$amount[$type.'_stock_count']) continue;

                        $this->results[ModelFBOStocks::class][$stock_hash = md5($amount['warehouse_name'])] ??= [
                            'last_request' => date('Y.m.d H:i:s'),
                            'active' => 'Y',
                            'name' => $amount['warehouse_name'],
                            'total' => 0
                        ];

                        $this->results[ModelFBOStocks::class][$stock_hash]['total'] += $amount[$type.'_stock_count'];

                        $this->results[FBOAmounts::class][implode(' | ', [$amount['sku'], $stock_hash, $type])] = [
                            'sku' => $amount['sku'],
                            'sid' => $stock_hash,
                            'type' => $type,
                            'amount' => $amount[$type.'_stock_count']
                        ];

                        //$this->history('history/ozon/'.$amount['sku'].'/fbo.csv', ' | '.implode(' : ', [$amount['warehouse_name'], $type, $amount[$type.'_stock_count']]));
                    }
                }
            }
            catch (Throwable $e)
            {
                Log::channel('error')->error(['OZON FBOStocks', $response->body(), (string) $e]); throw new ErrorException($response);
            }
        });

        $DB->statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach([ModelFBOStocks::class, FBOAmounts::class] as $class) if(count($values = $this->results[$class] ?? [])) match($class)
        {
            ModelFBOStocks::class => call_user_func(function () use ($values)
            {
                ModelFBOStocks::upsert($values, [], ['last_request', 'active', 'total' => DB::raw('`total` + values(`total`)')]);

                foreach(Logs::query()->where('entity', 'ozon_fbo_stocks')->pluck('value')->map(fn($value) => explode(' | ', $value)) as [$id, $name]) $this->sids[md5($name)] ??= $id * 1;

                Logs::query()->where('entity', 'ozon_fbo_stocks')->delete();
            }),
            FBOAmounts::class => call_user_func(function() use ($values)
            {
                foreach(array_chunk($values, 2000) as $chunk) FBOAmounts::shortUpsert(Arr::map($chunk, fn(array $value) => array_replace($value, ['sid' => $this->sids[$value['sid']]])));
            })
        };

        $DB->statement('SET FOREIGN_KEY_CHECKS=1;');

        if(count(array_filter($this->offset, 'strlen')))
        {
            Log::channel('ozon')->info(implode(' | ', ['OZON FBOStocks Iteration', $this->operation->counter, Time::during(time() - $start)])); Cache::set($this->hash, $this->offset, 500); return;
        }

        Log::channel('ozon')->info(implode(' | ', ['RESULTS', Time::during(time() - $start)]));
        Log::channel('ozon')->info(implode(' | ', ['OZON FBO Stocks', ...Arr::map($this->counts(ModelFBOStocks::class), fn($v, $k) => $k.': '.$v)]));
        Log::channel('ozon')->info(implode(' | ', ['OZON FBO Amounts', FBOAmounts::query()->count()]));

        Cache::delete($this->hash); $this->operation->update(['next_start' => $DB->raw('next_start + 10800'), 'counter' => 0]);
    }
}
