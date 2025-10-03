<?php namespace App\Services\Sources;

use App\Services\APIManager;

interface SourceInterface
{
    public function init(APIManager $manager): void;

    public function enqueue(string $endpoint, mixed $node = null, string $method = 'get', mixed $post = null, ...$custom): void;

    public function exec(callable $controller, array $responses): void;

    public function start(): void;

    public function finish(): void;
}
