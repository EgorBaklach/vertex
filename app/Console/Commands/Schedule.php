<?php namespace App\Console\Commands;

use App\Models\Dev\Schedule as ModelSchedule;
use ErrorException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

class Schedule extends Command
{
    protected $signature = 'schedule:pending {market?} {operation?}';
    protected $description = self::class;

    /** @var Process[] */
    private array $dispatcher = [];

    private function start(ModelSchedule $task): void
    {
        Cache::set($task->name, 'Y', $task->ttl); call_user_func([$this->dispatcher[] = new Process(['php', 'artisan', 'schedule:pending', $task->market, $task->operation]), 'start']);
    }

    private function pending(): void
    {
        $node = [];

        foreach(ModelSchedule::query()->where('active', 'Y')->get() as $task)
        {
            $manual = Cache::get(implode('_', ['MANUAL', 'IMPORT', $task->market, $task->operation])) ?? []; if(count($manual['sequence'] ?? [])) $node['has_sequences'][] = $manual;

            if(!$task->counter) $node['completed_operations'][$task->market][$task->operation] = true;

            if(Cache::get($task->name) === 'Y') continue;

            if($task->next_start && time() >= $task->next_start) $this->start($task); elseif(array_key_exists('sequence', $manual) && !count($manual['sequence']))
            {
                ModelSchedule::query()->where('market', $manual['marketplace'])->where('operation', match($manual['marketplace']){ 'WB', 'OZON' => 'PRICES', 'YM' => 'STOCKS'})->update(['next_start' => null]);

                Cache::delete($manual['hash']); Cache::set(implode('_', ['MANUAL', 'IMPORT', $task->market, 'IS_RUNNING']), $manual, 86400);

                $this->start($task);
            }
        }

        foreach($node['has_sequences'] ?? [] as $manual) if($node['completed_operations'][$manual['marketplace']][array_shift($manual['sequence'])] ?? false) Cache::set($manual['hash'], $manual, 86400);

        if(count($this->dispatcher)) while(true): sleep(1); foreach($this->dispatcher as $process) if($process->isRunning()) continue 2; break; endwhile;
    }

    private function dispatch(string $market, string $operation): void
    {
        try
        {
            if(!$task = ModelSchedule::query()->where('market', $market)->where('operation', $operation)->first()) throw new ErrorException('There isnt operation: '.$market.'_'.$operation);

            Log::channel(strtolower($task->market))->info('START | '.$task->handler->command); $task->increment('counter'); $task->update(['start' => time()]); $parameters = compact('market', 'operation');

            if($manual = Cache::get($hash = implode('_', ['MANUAL', 'IMPORT', $task->market, 'IS_RUNNING'])))
            {
                $parameters += match($operation)
                {
                    'PRODUCTS', 'FBS_STOCKS', 'PRICES', 'STOCKS' => ['tokens' => json_decode($manual['tokens'], true, 512, JSON_BIGINT_AS_STRING), 'is_manual' => true], default => []
                };

                match($task->name === 'OZON_PRICES' && $task->next_start > strtotime('today 12:00') ? 'OZON_FBS_STOCKS' : $task->name)
                {
                    'WB_PRICES', 'YM_STOCKS', 'OZON_FBS_STOCKS' => Cache::delete($hash), default => null
                };
            };

            call_user_func($task->handler->command::init($task, $this->output, App::make('endpoints', $parameters)));

            $task->update(['start' => null]); Log::channel(strtolower($task->market))->info('---------------------------');
        }
        catch (Throwable $e)
        {
            Log::channel('error')->error((string) $e);
        }

        Cache::delete($market.'_'.$operation);
    }

    public function handle(): int
    {
        call_user_func(fn($command, ...$params) => $this->{count($params) ? 'dispatch' : 'pending'}(...$params), ...array_filter(array_values($this->arguments()))); return Command::SUCCESS;
    }
}