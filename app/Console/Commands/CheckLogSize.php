<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

class CheckLogSize extends Command
{
    protected $signature = 'check:logSize';
    protected $description = self::class;

    private const total = 104857600; // 100 МБ

    public function handle(): int
    {
        try
        {
            foreach(File::allFiles(storage_path('logs')) as $file) if($file->getSize() >= self::total) File::delete($file);
        }
        catch (Throwable $e)
        {
            return Command::SUCCESS;
        }

        return Command::SUCCESS;
    }
}
