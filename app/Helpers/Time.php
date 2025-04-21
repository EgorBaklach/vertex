<?php namespace App\Helpers;

class Time
{
    public static function during(int $timestamp): string
    {
        return sprintf("%02d:%02d:%02d", floor($timestamp / 3600), (int) ($timestamp / 60) % 60, $timestamp % 60);
    }
}
