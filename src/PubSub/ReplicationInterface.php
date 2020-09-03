<?php

namespace BeyondCode\LaravelWebSockets\PubSub;

use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use stdClass;

interface ReplicationInterface
{
    /**
     * Boot the pub/sub provider (open connections, initial subscriptions, etc).
     *
     * @param  LoopInterface  $loop
     * @param  string|null  $factoryClass
     * @return self
     */
    public function boot(LoopInterface $loop, $factoryClass = null): self;

    /**
     * Publish a payload on a specific channel, for a specific app.
     *
     * @param  string  $appId
     * @param  string  $channel
     * @param  stdClass  $payload
     * @return bool
     */
    public function publish($appId, string $channel, stdClass $payload): bool;

    /**
     * Subscribe to receive messages for a channel.
     *
     * @param  string  $appId
     * @param  string  $channel
     * @return bool
     */
    public function subscribe($appId, string $channel): bool;

    /**
     * Unsubscribe from a channel.
     *
     * @param  string  $appId
     * @param  string  $channel
     * @return bool
     */
    public function unsubscribe($appId, string $channel): bool;

    /**
     * Subscribe to the app's pubsub keyspace.
     *
     * @param  mixed  $appId
     * @return bool
     */
    public function subscribeToApp($appId): bool;

    /**
     * Unsubscribe from the app's pubsub keyspace.
     *
     * @param  mixed  $appId
     * @return bool
     */
    public function unsubscribeFromApp($appId): bool;

    /**
     * Add a member to a channel. To be called when they have
     * subscribed to the channel.
     *
     * @param  string  $appId
     * @param  string  $channel
     * @param  string  $socketId
     * @param  string  $data
     * @return void
     */
    public function joinChannel($appId, string $channel, string $socketId, string $data);

    /**
     * Remove a member from the channel. To be called when they have
     * unsubscribed from the channel.
     *
     * @param  string  $appId
     * @param  string  $channel
     * @param  string  $socketId
     * @return void
     */
    public function leaveChannel($appId, string $channel, string $socketId);

    /**
     * Retrieve the full information about the members in a presence channel.
     *
     * @param  string  $appId
     * @param  string  $channel
     * @return PromiseInterface
     */
    public function channelMembers($appId, string $channel): PromiseInterface;

    /**
     * Get the amount of users subscribed for each presence channel.
     *
     * @param  string  $appId
     * @param  array  $channelNames
     * @return PromiseInterface
     */
    public function channelMemberCounts($appId, array $channelNames): PromiseInterface;

    /**
     * Get the amount of unique connections.
     *
     * @param  mixed  $appId
     * @return null|int|\React\Promise\PromiseInterface
     */
    public function appConnectionsCount($appId);
}
