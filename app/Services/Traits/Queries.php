<?php namespace App\Services\Traits;

use App\Helpers\Arr;
use App\Helpers\Func;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;

trait Queries
{
    private static array $file_checks = [];

    protected function updateInstances(Builder $query): void
    {
        array_unshift($query->getQuery()->bindings['where'], 'D', 'D'); $query->lockForUpdate();

        DB::transaction(fn() => $query->update(['active' => DB::raw('
        CASE
            WHEN `active` = ? THEN NULL
            WHEN `active` IS NOT NULL THEN ?
            ELSE `active`
        END')]));
    }

    protected function counts(string|Model $model): array
    {
        $total = []; foreach($model::query()->groupBy('active')->orderBy('active')->get(DB::raw('active, count(*) as cnt')) as $c) $total[$c['active'] ?? 'NULL'] = $c['cnt'];

        return ['active' => $total['Y'] ?? 0, 'unactive' => $total['D'] ?? 0, 'delete' => $total['NULL'] ?? 0];
    }

    protected function keep(...$fields): array
    {
        return array_combine($fields, array_map(fn($v) => DB::raw('CASE WHEN VALUES(`'.$v.'`) IS NULL THEN `'.$v.'` ELSE VALUES(`'.$v.'`) END'), $fields));
    }

    protected function replace(...$fields): array
    {
        return array_combine($fields, array_map(fn($v) => DB::raw('CASE WHEN `'.$v.'` IS NULL THEN VALUES(`'.$v.'`) ELSE `'.$v.'` END'), $fields));
    }

    private function check(string $path): bool
    {
        return self::$file_checks[$path] ?? !self::$file_checks[$path] = true;
    }

    protected function history(string $path, string $content): void
    {
        $separator = !$this->check($path) ? PHP_EOL : NULL; Storage::disk('local')->append($path, $separator === PHP_EOL ? date('Y-m-d H:i:s').$content : $content, $separator);
    }

    public function log(ReflectionClass $ref, string $method): void
    {
        Log::channel('ym')->info(implode(' | ', ['YM '.$ref->getShortName(), ...Func::call($ref->getName(), fn(string|Model $class): array => match($method)
        {
            'update' => Arr::map($this->counts($class), fn($v, $k) => $k.': '.$v), default => [$class::query()->count()]
        })]));
    }
}
