<?php namespace App\Services;

use App\Helpers\Time;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RMHistory extends MSAbstract
{
    public function __invoke(): void
    {
        $start = time();

        try
        {
            Storage::disk('local')->deleteDirectory(implode('/', ['history', ...$this->operation->handler->params]));
        }
        catch (Throwable $e)
        {
            Log::channel('error')->error([$this->operation->market.' RMHistory', (string) $e]);
        }

        $this->operation->update(['next_start' => strtotime('first day of next month midnight'), 'counter' => 0]);

        Log::info(implode(' | ', ['RM History', Time::during(time() - $start)]));
    }
}
