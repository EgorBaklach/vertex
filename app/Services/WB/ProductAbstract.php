<?php namespace App\Services\WB;

use App\Exceptions\Http\ErrorException;
use App\Helpers\Time;
use App\Models\Dev\Logs;
use App\Services\Traits\Repeater;
use App\Models\Dev\WB\{Categories, Files, PPV, Products as ModelProducts, Properties, PV, Settings, Sizes};
use App\Services\APIManager;
use App\Services\MSAbstract;
use App\Services\Sources\Tokens;
use App\Services\Traits\Queries;
use ErrorException as NativeErrorException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\{Cache, DB, Log};
use Throwable;

abstract class ProductAbstract extends MSAbstract
{
    use Queries, Repeater;

    protected APIManager $manager;

    protected array $skip = [];

    protected int $start;

    private array $vids = [];

    protected const limit = 50;

    public function __invoke(): void
    {
        $this->start = time(); $this->manager = $this->endpoint(Tokens::class, APIManager::class); $DB = DB::connection('dev'); $DB->statement('SET FOREIGN_KEY_CHECKS=0;');

        $tvend_pid = Cache::remember('wb_tvend_pid', 3600, fn() => Settings::whereLike('variable', 'tnved:pid')->pluck('value')->first() * 1 ?: 15000001);
        $zero_properties = Cache::remember('wb_zero_properties', 3600, fn() => array_fill_keys(Properties::query()->whereLike('count', 0)->pluck('id')->all(), true));

        if($this->operation->counter === 1) Categories::query()->update(['cnt' => 0]); $this->start();

        while(true)
        {
            $start = floor(microtime(true) * 1000); $this->collect(); $this->results = []; if(!$this->manager->count()) break;

            $this->manager->init(function(Response $response, $attributes, $operator, $tid, callable $controller) use ($tvend_pid, $zero_properties)
            {
                try
                {
                    if($this->skip[$operator][$tid] = $response->status() >= 400) throw new NativeErrorException('Status Error: '.$response->status());

                    foreach($controller($response->json()) as $product)
                    {
                        $this->results[Categories::class][$product['subjectID']] ??= ['id' => $product['subjectID'], 'cnt' => 0]; $this->results[Categories::class][$product['subjectID']]['cnt']++;

                        $this->results[ModelProducts::class][$product['nmID']] = [
                            'nmID' => $product['nmID'],
                            'imtID' => $product['imtID'] ?? null,
                            'nmUUID' => $product['nmUUID'] ?? null,
                            'last_request' => date('Y-m-d H:i:s'),
                            'active' => !count(array_filter(['photos', 'sizes'], fn($k) => !count($product[$k] ?? []))) ? 'Y' : null,
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
                    Log::channel('error')->error(implode(' | ', ['WB '.$this->operation->operation,  $response->status(), $response->body()])); usleep(100); throw $this->isAccess($attributes['token']) ? $e : new ErrorException($response);
                }
            });

            /** @var string|Model $class */

            foreach([Properties::class, ModelProducts::class, PV::class, PPV::class, Files::class, Sizes::class, Categories::class] as $class) if(count($values = $this->results[$class] ?? [])) match($class)
            {
                ModelProducts::class => $class::upsert($values, [], array_merge($this->keep('title', 'description', 'brand'), $this->replace('imtID', 'nmUUID', 'updatedAt', 'dimensions'), ['last_request', 'active', 'inTrash', 'cid'])),

                PV::class => call_user_func(function() use ($values)
                {
                    PV::shortUpsert($values); foreach(Logs::query()->where('entity', 'wb_pv')->pluck('value')->map(fn($value) => explode(' | ', $value)) as [$pid, $id, $value]) $this->vids[$pid.' | '.md5($value)] ??= $id * 1;

                    Logs::query()->where('entity', 'wb_pv')->delete();
                }),

                PPV::class => PPV::shortUpsert(Arr::map($values, fn(array $value) => array_key_exists($hash = $value['pid'].' | '.md5($value['value']), $this->vids) ? array_replace($value, ['pvid' => $this->vids[$hash], 'value' => null]) : $value)),
                default => $class::shortUpsert($values)
            };

            if(floor(microtime(true) * 1000) - $start <= 500) usleep(1500000);
        }

        $DB->statement('SET FOREIGN_KEY_CHECKS=1;');

        if($this->finish())
        {
            Log::channel('wb')->info(implode(' | ', ['RESULTS', Time::during(time() - $this->start)]));
            Log::channel('wb')->info(implode(' | ', ['WB Products', ...Arr::map($this->counts(ModelProducts::class), fn($v, $k) => $k.': '.$v)]));
            Log::channel('wb')->info(implode(' | ', ['WB Property values', ...Arr::map($this->counts(PV::class), fn($v, $k) => $k.': '.$v)]));
            Log::channel('wb')->info(implode(' | ', ['WB Sizes', ...Arr::map($this->counts(Sizes::class), fn($v, $k) => $k.': '.$v)]));
        }
    }

    abstract protected function start(): void;

    abstract protected function collect(): void;

    abstract protected function finish(): bool;
}
