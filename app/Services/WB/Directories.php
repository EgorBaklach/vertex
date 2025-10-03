<?php namespace App\Services\WB;

use App\Helpers\Time;
use App\Services\APIManager;
use App\Services\Sources\Tokens;
use App\Exceptions\Http\{ErrorException, RepeatException};
use App\Models\Dev\Logs;
use App\Models\Dev\WB\{PV, Settings};
use App\Services\MSAbstract;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class Directories extends MSAbstract
{
    public function __invoke(): void
    {
        $start = time();

        foreach(Settings::whereLike('variable', '%pid')->get() as $option)
        {
            if($option->variable !== 'tnved:pid') $this->endpoint(Tokens::class, current(explode(':', $option->variable)), $option->value * 1);
        }

        $this->endpoint(Tokens::class, APIManager::class)->init(function(Response $response, $attributes, $operation, $pid)
        {
            if(!$response->successful()) throw new ($response->status() === 429 ? RepeatException::class : ErrorException::class)($response);

            foreach($response->json('data') ?? [] as $value) $this->results[] = [
                'last_request' => date('Y-m-d H:i:s'),
                'active' => 'Y',
                'pid' => $pid,
                'value' => match ($operation)
                {
                    'countries', 'colors' => $value['name'],
                    default => $value
                },
                'params' => match ($operation)
                {
                    'countries' => $value['fullName'],
                    'colors' => $value['parentName'],
                    default => null
                }
            ];
        });

        PV::shortUpsert($this->results);

        Logs::query()->where('entity', 'wb_pv')->delete();

        $this->operation->update(['next_start' => null, 'counter' => 0]);

        Log::channel('wb')->info(implode(' | ', ['WB Directories', Time::during(time() - $start)]));
    }
}
