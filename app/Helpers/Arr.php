<?php namespace App\Helpers;

use ReflectionFunction;
use Throwable;

class Arr
{

    public static function get($node, ...$keys): mixed
    {
        try
        {
            while(is_callable($node)) $node = $node(...array_splice($keys, 0, call_user_func([new ReflectionFunction($node), 'getNumberOfParameters'])));

            return is_array($node) && count($keys) ? self::get($node[array_shift($keys)], ...$keys) : $node;
        }
        catch (Throwable $e)
        {
            return null;
        }
    }

    public static function map(...$args): array
    {
        return \Illuminate\Support\Arr::map(...$args);
    }
}
