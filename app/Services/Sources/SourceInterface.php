<?php namespace App\Services\Sources;

use SplQueue;

interface SourceInterface
{
    public function init(SplQueue $queue): void;

    public function enqueue(string $endpoint, $data = null, string $method = 'get', mixed $post = null, ...$custom): void;

    public function exec(callable $controller, array $responses): void;

    public function start(): void;

    public function finish(): void;
}
