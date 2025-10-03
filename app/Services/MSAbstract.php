<?php namespace App\Services;

use App\Helpers\Arr;
use App\Models\Dev\Schedule;
use ArrayObject;
use Illuminate\Console\OutputStyle;

abstract class MSAbstract
{
    protected string $hash;

    protected array $results = [];

    protected const param = 'get';

    protected function __construct(protected Schedule $operation, protected OutputStyle $output, private readonly ArrayObject $endpoints)
    {
        $this->hash = $operation->name.':'.static::param;
    }

    public static function init(...$values): self
    {
        return new static(...$values);
    }

    protected function endpoint(string $source, $node = null, ...$attributes): bool|null|array|ArrayObject|APIManager
    {
        foreach([[$node], [static::class, $node], [static::class]] as $params) if(!is_null($resolve = Arr::get($this->endpoints, $source, ...$params, ...$attributes))) return $resolve; return null;
    }

    abstract public function __invoke(): void;
}
