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

    private function dispatch(string $market, string $operation): void
    {
        /** @global ModelSchedule $task */

        try
        {
            if(!($task = ModelSchedule::query()->where('market', $market)->where('operation', $operation)->get()->first()) instanceof ModelSchedule) throw new ErrorException('There isnt operation: '.$market.'_'.$operation);

            Log::channel(strtolower($task->market))->info('START | '.$task->handler->command); $task->increment('counter');

            call_user_func($task->handler->command::init($task, $this->output, App::make('endpoints')));

            Log::channel(strtolower($task->market))->info('---------------------------');
        }
        catch (Throwable $e)
        {
            Log::channel('error')->error((string) $e);
        }

        Cache::delete($market.'_'.$operation);
    }

    private function pending(): void
    {
        /** @var ModelSchedule $task */ $dispatcher = [];

        foreach(ModelSchedule::query()->where('active', 'Y')->where('next_start', '<=', time())->get(['market', 'operation', 'ttl']) as $task)
        {
            if(Cache::get($task->name) === 'Y') continue; Cache::set($task->name, 'Y', $task->ttl);

            call_user_func([$dispatcher[$task->name] = new Process(['php', 'artisan', 'schedule:pending', $task->market, $task->operation]), 'start']);
        }

        if(count($dispatcher)) while(true): sleep(1); foreach($dispatcher as $process) if($process->isRunning()) continue 2; break; endwhile;
    }

    public function handle(): int
    {
        call_user_func(fn($command, ...$params) => $this->{count($params) ? 'dispatch' : 'pending'}(...$params), ...array_filter(array_values($this->arguments()))); return Command::SUCCESS;
    }
}
