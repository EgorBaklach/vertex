<?php namespace App\Services\WB;

use App\Exceptions\Http\ErrorException;
use App\Helpers\Time;
use App\Models\Dev\Logs;
use App\Models\Dev\MarketplaceApiKey;
use App\Models\Dev\Schedule;
use App\Services\APIManager;
use App\Services\MSAbstract;
use App\Services\Sources\Tokens;
use App\Services\Traits\{Queries, Repeater, Tracker};
use ErrorException as NativeErrorException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Models\Dev\WB\{Categories, Files, PPV, Products as ModelProducts, Properties, PV, Settings, Sizes};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class Products extends MSAbstract
{
    use Queries, Repeater, Tracker;

    private array $cursors = [];

    private array $skip = [];

    private array $vids = [];

    protected const limit = 1000;

    private function remains(string $state): int
    {
        return count(array_filter($this->cursors[$state] ?? [], fn($v) => $v !== false));
    }

    public function __invoke(): void
    {
        /** @var string|Model $class */

        $start = time(); $day = date('N') * 1; $manager = $this->endpoint(Tokens::class, APIManager::class); $DB = DB::connection('dev');

        $tvend_pid = Cache::remember('wb_tvend_pid', 3600, fn() => Settings::whereLike('variable', 'tnved:pid')->pluck('value')->first() * 1 ?: 15000001);
        $zero_properties = Cache::remember('wb_zero_properties', 3600, fn() => array_fill_keys(Properties::query()->whereLike('count', 0)->pluck('id')->all(), true));

        if($this->operation->counter === 1)
        {
            foreach ([ModelProducts::class, Sizes::class] as $class) $this->updateInstances($class::query()->whereHas('token', fn(Builder $b) => $b->whereJsonContains('days', $day))); Categories::query()->update(['cnt' => 0]);
        }

        foreach($this->cursors = Cache::get($this->hash) ?? [] as $operator => $cursors) foreach ($cursors as $tid => $cursor) if($cursor === false) $this->skip[$operator][$tid] = true;

        $manager->source->throw = function(Throwable $e, $attributes, ...$data) use ($manager)
        {
            $manager->source->enqueue(...array_values($attributes), ...$data); $attributes['token']->inset('abort');
        };

        $DB->statement('SET FOREIGN_KEY_CHECKS=0;');

        while(true)
        {
            $this->results = [];

            foreach($this->endpoint(Tokens::class) as [$operator, $endpoint, $post])
            {
                $limit = match($this->remains($operator))
                {
                    1, 2 => 100, 3 => 75, 4, 5 => 50, default => 30
                };

                foreach($manager->source->all('WB') as $tid => $token)
                {
                    if(!in_array($day, $token->days) || ($this->skip[$operator][$tid] ?? false)) continue; $post['settings']['cursor'] = compact('limit') + ($this->cursors[$operator][$tid] ?? []);

                    $manager->enqueue($endpoint, $token, 'post', $post, $operator, $tid);
                }
            }

            if(!$manager->count()) break;

            $manager->init(function(Response $response, $attributes, $operator, $tid) use ($tvend_pid, $zero_properties)
            {
                try
                {
                    if($this->skip[$operator][$tid] = $response->status() >= 400) throw new NativeErrorException('Status Error: '.$response->status()); $result = $response->json();

                    if(!$this->cursors[$operator][$tid] = count($result['cards']) ? $result['cursor'] : false) $this->skip[$operator][$tid] = true;

                    foreach($result['cards'] as $product)
                    {
                        $this->results[Categories::class][$product['subjectID']] ??= ['id' => $product['subjectID'], 'cnt' => 0]; $this->results[Categories::class][$product['subjectID']]['cnt']++;

                        $this->results[ModelProducts::class][$product['nmID']] = [
                            'nmID' => $product['nmID'],
                            'imtID' => $product['imtID'] ?? null,
                            'nmUUID' => $product['nmUUID'] ?? null,
                            'last_request' => date('Y-m-d H:i:s'),
                            'active' => 'Y',
                            'tid' => $tid,
                            'inTrash' => $operator === 'trash' ? 'Y' : null,
                            'createdAt' => $product['createdAt'],
                            'updatedAt' => $product['updatedAt'] ?? $product['trashedAt'] ?? null,
                            'cid' => $product['subjectID'],
                            'vendorCode' => $product['vendorCode'],
                            'brand' => $product['brand'] ?? null,
                            'title' => $product['title'] ?? null,
                            'description' => $product['description'] ?? null,
                            'dimensions' => !array_key_exists('dimensions', $product) ? null : implode(':', [
                                $product['dimensions']['width'] ?? '',
                                $product['dimensions']['height'] ?? '',
                                $product['dimensions']['length'] ?? '',
                                $product['dimensions']['weightBrutto'] ?? '',
                                $product['dimensions']['isValid'] ?? '',
                            ])
                        ];

                        if(array_key_exists('photos', $product)) foreach($product['photos'] as $photo) $this->results[Files::class][md5($photo['big'])] ??= [
                            'nmID' => $product['nmID'],
                            'last_request' => date('Y-m-d H:i:s'),
                            'type' => 'picture',
                            'url' => $photo['big']
                        ];

                        if(strlen($product['video'] ?? '')) $this->results[Files::class][md5($product['video'])] ??= [
                            'nmID' => $product['nmID'],
                            'last_request' => date('Y-m-d H:i:s'),
                            'type' => 'video',
                            'url' => $product['video']
                        ];

                        foreach($product['sizes'] ?? [] as $size)
                        {
                            foreach (count($size['skus']) ? $size['skus'] : ['-'] as $sku)
                            {
                                $this->results[Sizes::class][implode(':', [$product['nmID'], $size['chrtID'], $sku])] = [
                                    'chrtID' => $size['chrtID'],
                                    'nmID' => $product['nmID'],
                                    'tid' => $tid,
                                    'last_request' => date('Y-m-d H:i:s'),
                                    'active' => 'Y',
                                    'techSize' => $size['techSize'] !== '0' ? $size['techSize'] : null,
                                    'wbSize' => strlen($size['wbSize']) ? $size['wbSize'] : null,
                                    'sku' => $sku
                                ];
                            }
                        }

                        foreach($product['characteristics'] ?? [] as $property)
                        {
                            $type = null;

                            foreach((array) $property['value'] as $value)
                            {
                                $type = intval($value) ? 4 : 0;

                                $this->results[PPV::class][$product['nmID'].' | '.$property['id'].' | '.md5($value)] ??= [
                                    'nmID' => $product['nmID'],
                                    'pid' => $property['id'],
                                    'pvid' => 0,
                                    'value' => $value
                                ];

                                if(!array_key_exists($property['id'], $zero_properties))
                                {
                                    $this->results[PV::class][$property['id'].' | '.md5($value)] ??= [
                                        'last_request' => date('Y-m-d H:i:s'),
                                        'active' => 'Y',
                                        'pid' => $property['id'],
                                        'value' => $value,
                                        'bind' => $property['id'] === $tvend_pid ? $product['subjectID'] : ''
                                    ];
                                }
                            }

                            $this->results[Properties::class][$property['id']] ??= [
                                'id' => $property['id'],
                                'last_request' => date('Y-m-d H:i:s'),
                                'active' => 'Y',
                                'name' => $property['name'],
                                'count' => is_array($property['value']) ? count($property['value']) : 0,
                                'type' => $type ?: intval(!is_array($property['value']))
                            ];
                        }
                    }
                }
                catch (Throwable $e)
                {
                    if($response->status() !== 429) Log::channel('error')->error(implode(' | ', ['WB Products',  $response->status(), $response->body()]));

                    usleep(100); throw $this->isAccess($attributes['token']) ? $e : new ErrorException($response);
                }
            });

            foreach([Properties::class, ModelProducts::class, PV::class, PPV::class, Files::class, Sizes::class, Categories::class] as $class) if(count($values = $this->results[$class] ?? [])) match($class)
            {
                ModelProducts::class => $class::query()->upsert($values, [], array_merge($this->keep('title', 'description', 'brand'), $this->replace('imtID', 'nmUUID', 'updatedAt', 'dimensions'), ['last_request', 'active', 'inTrash', 'cid'])),

                PV::class => call_user_func(function() use ($values)
                {
                    PV::shortUpsert($values); foreach(Logs::query()->where('entity', 'wb_pv')->pluck('value')->map(fn($value) => explode(' | ', $value)) as [$pid, $id, $value]) $this->vids[$pid.' | '.md5($value)] ??= $id * 1;

                    Logs::query()->where('entity', 'wb_pv')->delete();
                }),

                PPV::class => PPV::shortUpsert(Arr::map($values, fn(array $value) => array_key_exists($hash = $value['pid'].' | '.md5($value['value']), $this->vids) ? array_replace($value, ['pvid' => $this->vids[$hash], 'value' => null]) : $value)),
                default => $class::query()->upsert($values, [])
            };

            if($this->due(500)) usleep(1000000);
        }

        $DB->statement('SET FOREIGN_KEY_CHECKS=1;');

        if(array_sum(array_map([$this, 'remains'], array_keys($this->cursors))))
        {
            Log::channel('wb')->info(implode(' | ', ['WB Products Iteration', $this->operation->counter, Time::during(time() - $start)])); Cache::set($this->hash, $this->cursors, 1800); return;
        }

        MarketplaceApiKey::query()->where('marketplace', 'WB')->update(['active' => 'Y']); Cache::delete($this->hash);

        Schedule::shortUpsert([
            ['market' => 'WB', 'operation' => 'PRODUCTS', 'next_start' => strtotime('tomorrow 2:00'), 'counter' => 0],
            ['market' => 'WB', 'operation' => 'FBS_STOCKS', 'next_start' => time(), 'counter' => 0]
        ]);

        Log::channel('wb')->info(implode(' | ', ['RESULTS', Time::during(time() - $start)]));
        Log::channel('wb')->info(implode(' | ', ['WB Products', ...Arr::map($this->counts(ModelProducts::class), fn($v, $k) => $k.': '.$v)]));
        Log::channel('wb')->info(implode(' | ', ['WB Property values', ...Arr::map($this->counts(PV::class), fn($v, $k) => $k.': '.$v)]));
        Log::channel('wb')->info(implode(' | ', ['WB Sizes', ...Arr::map($this->counts(Sizes::class), fn($v, $k) => $k.': '.$v)]));
    }
}
