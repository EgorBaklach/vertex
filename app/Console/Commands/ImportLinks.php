<?php namespace App\Console\Commands;

use App\Models\Sora\Pictures;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportLinks extends Command
{
    protected $signature = 'import:links';
    protected $description = self::class;

    private array $records = [];

    private const limit = 5000;

    private function import(): void
    {
        Pictures::upsert($this->records, []); $this->records = [];
    }

    public function handle(): int
    {
        try
        {
            foreach(Storage::disk('public')->allFiles('links') as $file)
            {
                foreach(file(Storage::disk('public')->path($file)) as $url)
                {
                    $path = parse_url(trim($url), PHP_URL_PATH); $name = explode('/', $path); [$number, $extension] = explode('.', array_pop($name));

                    $this->records[$number] = ['status' => null] + compact('number', 'extension'); if(count($this->records) >= self::limit) $this->import();
                }

                if(count($this->records)) $this->import();
            }
        }
        catch (Throwable $e)
        {
            Log::channel('error')->error((string) $e); return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}