<?php namespace App\Models\Dev\Traits;

trait CustomQueries
{
    public static function shortUpsert(array $insert): int
    {
        return self::upsert($insert, []);
    }
}
