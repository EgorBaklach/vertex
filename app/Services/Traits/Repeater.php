<?php namespace App\Services\Traits;

use App\Models\Dev\MarketplaceApiKey;

trait Repeater
{
    /** @var int[] */
    private array $repeats = [];

    protected function isAccess(MarketplaceApiKey $token): bool
    {
        $this->repeats[$token->id] ??= 0; return ++$this->repeats[$token->id] <= static::limit ?? 10;
    }
}
