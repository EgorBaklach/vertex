<?php namespace App\Services\Traits;

use Illuminate\Support\Facades\App;

trait Tracker
{
    private int $tick = 1;

    public function due(int $ttl): bool
    {
        return floor(microtime(true) * 1000) > App::make('timestamp') + $ttl * $this->tick && !!++$this->tick;
    }
}
