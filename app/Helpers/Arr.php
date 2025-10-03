<?php namespace App\Helpers;

use ArrayAccess;
use Illuminate\Support\Arr as LaravelArr;
use ReflectionFunction;
use Throwable;

class Arr
{
    public static function get($node, ...$keys): mixed
    {
        try
        {
            while(is_callable($node)) $node = $node(...array_splice($keys, 0, call_user_func([new ReflectionFunction($node), 'getNumberOfParameters'])));

            return self::checkArray($node) && count($keys) ? self::get($node[array_shift($keys)], ...$keys) : $node;
        }
        catch (Throwable $e)
        {
            return null;
        }
    }

    private static function checkArray(mixed $node): bool
    {
        return is_array($node) || $node instanceof ArrayAccess;
    }

    public static function map(...$args): array
    {
        return LaravelArr::map(...$args);
    }
}
