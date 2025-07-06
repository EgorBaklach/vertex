<?php namespace App\Console\Commands\Sora;

use App\Models\Sora\Pictures;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Interfaces\ImageManagerInterface;
use Symfony\Component\Process\Process;

class Import extends Command
{
    protected $signature = 'sora:import {number?} {gen?}';
    protected $description = self::class;

    private function pending(string $number, int $gen): void
    {
        App::make(ImageManagerInterface::class)->read(Storage::disk('public')->path('sora/'.$number.'/'.$number.'_'.$gen.'.webp'))->toJpg(95)->save('/pool3_mockups/sora_out/'.$number.'.jpg');
    }

    private function dispatch(): void
    {
        /** @var Pictures $picture */$last_id = 0; $dispatcher = []; $counter = 0;

        while(true)
        {
            $pictures = Pictures::query()->where('id', '>', $last_id)->whereNotNull('selectGen')->orderBy('id')->limit(10); if(!$pictures->count()) break;

            foreach($pictures->get() as $picture) call_user_func([$dispatcher[$last_id = $picture->id] = new Process(['php', 'artisan', 'sora:import', $picture->number, $picture->selectGen*1]), 'start']);

            if(!$int = count($dispatcher)) break; $counter += $int; while(true): sleep(1); foreach($dispatcher as $process) if($process->isRunning()) continue 2; break; endwhile;

            $dispatcher = []; $this->output->writeln($counter); sleep(1);
        }
    }

    public function handle(): int
    {
        call_user_func(fn($command, ...$params) => $this->{count($params) ? 'pending' : 'dispatch'}(...$params), ...array_filter(array_values($this->arguments()))); return Command::SUCCESS;
    }
}