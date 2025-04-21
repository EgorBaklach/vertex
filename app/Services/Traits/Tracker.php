<?php namespace App\Services\Traits;

trait Tracker
{
    private int $tick = 1;

    public function due(int $timestamp): bool
    {
        return time() > $timestamp + self::ttl * $this->tick && !!++$this->tick;
    }
}
