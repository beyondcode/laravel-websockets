<?php

namespace BeyondCode\LaravelWebSockets\PubSub;

use stdClass;
use React\EventLoop\LoopInterface;

interface ReplicationInterface
{
    /**
     * Boot the pub/sub provider (open connections, initial subscriptions, etc.)
     *
     * @param LoopInterface $loop
     * @return self
     */
    public function boot(LoopInterface $loop): self;

    /**
     * Publish a payload on a specific channel, for a specific app
     *
     * @param string $appId
     * @param string $channel
     * @param stdClass $payload
     * @return bool
     */
    public function publish(string $appId, string $channel, stdClass $payload): bool;

    /**
     * Subscribe to receive messages for a channel
     *
     * @param string $channel
     * @return bool
     */
    public function subscribe(string $appId, string $channel): bool;

    /**
     * Unsubscribe from a channel
     *
     * @param string $channel
     * @return bool
     */
    public function unsubscribe(string $appId, string $channel): bool;
}
