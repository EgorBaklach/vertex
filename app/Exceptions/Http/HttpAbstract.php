<?php namespace App\Exceptions\Http;

use App\Models\Dev\{Agents, MarketplaceApiKey, Proxies};
use ErrorException;
use Illuminate\Http\Client\Response;

/**
 * @property string $endpoint
 * @property MarketplaceApiKey $token
 * @property Proxies|null $proxy
 * @property Agents $agent
 * @property string $method
 * @property array $post
 * @property array $custom
 */
abstract class HttpAbstract extends ErrorException
{
    private array $attributes = [];

    public function __construct(public readonly Response $response)
    {
        parent::__construct($response->getReasonPhrase(), $response->status());
    }

    public function values(array $attributes, ...$custom): self
    {
        $this->attributes = $attributes + compact('custom'); return $this;
    }

    public function __get($value): mixed
    {
        return $this->attributes[$value] ?? null;
    }
}
