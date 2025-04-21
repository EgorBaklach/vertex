<?php namespace App\Helpers;

class Func
{
    public static function call(...$values): mixed
    {
        return call_user_func(array_pop($values), ...$values);
    }
}
