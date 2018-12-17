<?php

namespace BeyondCode\LaravelWebSockets\PubSub;

use React\EventLoop\LoopInterface;

interface PubSubInterface
{
    public function publish(string $appId, array $payload): bool;

    public function subscribe(LoopInterface $loop): PubSubInterface;
}