<?php namespace App\Contexts;

use App\Helpers\Func;
use App\Models\Dev\YM\{CP, PPV, Products};
use Illuminate\Support\Arr;

/**
 * @property array $values
 * @property array $pids
 * @property array $cp
 */
class YMCProperties
{
    private array $params = [];

    public function ppv(PPV $ppv): void
    {
        $this->params['values'][$ppv->offer_id][$ppv->property_id][] = ['value' => strlen(trim($ppv->value)) ? trim($ppv->value) : trim($ppv->pv->value), 'unit' => $ppv->unit->name ?? null];
        $this->params['pids'][$ppv->property_id] ??= $ppv->property_id;
    }

    public function cp(CP $cp): void
    {
        $this->params['cp'][$cp->pid][$cp->cid] = $cp;
    }

    public function __get($name): ?array
    {
        return $this->params[$name] ?? null;
    }

    public function __invoke(Products $product): string
    {
        return implode('<br>', array_filter(Arr::map($this->values[$product->offerId] ?? [], fn($values, $pid) => $this->property($this->cp[$pid][$product->cid] ?? current($this->cp[$pid]), $values))));
    }

    private function property(CP $cp, array $values): string
    {
        return '**'.$cp->name.'**: '.implode(', ', Arr::map($values, fn($v) => $v['value'].Func::call($v['unit'] ?? $cp->du->unit->name ?? null, fn(?string $unit) => strlen($unit) ? ' '.$unit : '')));
    }
}
