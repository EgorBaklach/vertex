<?php namespace App\Helpers\String;

enum Cutter
{
    case after;
    case before;

    public function __invoke(string $needle, string $haystack): string
    {
        return match ($this->name)
        {
            'after' => mb_substr($haystack, stripos($haystack, $needle) + strlen($needle)),
            'before' => mb_substr($haystack, 0, stripos($haystack, $needle)),
        };
    }
}
