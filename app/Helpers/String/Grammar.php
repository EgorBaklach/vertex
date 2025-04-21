<?php namespace App\Helpers\String;

class Grammar
{
    public static function plural(string $number, array $titles): string
    {
        return $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : [2, 0, 1, 1, 1, 2][min($number % 10, 5)]];
    }
}
