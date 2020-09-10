<?php

namespace BeyondCode\LaravelWebSockets\Contracts;

use Ratchet\ConnectionInterface;
use React\Promise\PromiseInterface;
use stdClass;
use React\EventLoop\LoopInterface;

interface ChannelManager
{
    /**
     * Create a new channel manager instance.
     *
     * @param  LoopInterface  $loop
     * @param  string|null  $factoryClass
     * @return void
     */
    public function __construct(LoopInterface $loop, $factoryClass = null);

    /**
     * Find the channel by app & name.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @return null|BeyondCode\LaravelWebSockets\Channels\Channel
     */
    public function find($appId, string $channel);

    /**
     * Find a channel by app & name or create one.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @return BeyondCode\LaravelWebSockets\Channels\Channel
     */
    public function findOrCreate($appId, string $channel);

    /**
     * Get all channels for a specific app
     * for the current instance.
     *
     * @param  string|int  $appId
     * @return \React\Promise\PromiseInterface[array]
     */
    public function getLocalChannels($appId): PromiseInterface;

    /**
     * Get all channels for a specific app
     * across multiple servers.
     *
     * @param  string|int  $appId
     * @return \React\Promise\PromiseInterface[array]
     */
    public function getGlobalChannels($appId): PromiseInterface;

    /**
     * Remove connection from all channels.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function unsubscribeFromAllChannels(ConnectionInterface $connection);

    /**
     * Subscribe the connection to a specific channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  string  $channelName
     * @param  stdClass  $payload
     * @return void
     */
    public function subscribeToChannel(ConnectionInterface $connection, string $channelName, stdClass $payload);

    /**
     * Unsubscribe the connection from the channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  string  $channelName
     * @param  stdClass  $payload
     * @return void
     */
    public function unsubscribeFromChannel(ConnectionInterface $connection, string $channelName, stdClass $payload);

    /**
     * Subscribe the connection to a specific channel.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function subscribeToApp($appId);

    /**
     * Unsubscribe the connection from the channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  string|int  $appId
     * @return void
     */
    public function unsubscribeFromApp($appId);

    /**
     * Get the connections count on the app
     * for the current server instance.
     *
     * @param  string|int  $appId
     * @param  string|null  $channelName
     * @return \React\Promise\PromiseInterface
     */
    public function getLocalConnectionsCount($appId, string $channelName = null): PromiseInterface;

    /**
     * Get the connections count
     * across multiple servers.
     *
     * @param  string|int  $appId
     * @param  string|null  $channelName
     * @return \React\Promise\PromiseInterface
     */
    public function getGlobalConnectionsCount($appId, string $channelName = null): PromiseInterface;

    /**
     * Broadcast the message across multiple servers.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @param  stdClass  $payload
     * @return bool
     */
    public function broadcastAcrossServers($appId, string $channel, stdClass $payload);

    /**
     * Handle the user when it joined a presence channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  stdClass  $user
     * @param  string  $channel
     * @param  stdClass  $payload
     * @return void
     */
    public function userJoinedPresenceChannel(ConnectionInterface $connection, stdClass $user, string $channel, stdClass $payload);

    /**
     * Handle the user when it left a presence channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  stdClass  $user
     * @param  string  $channel
     * @param  stdClass  $payload
     * @return void
     */
    public function userLeftPresenceChannel(ConnectionInterface $connection, stdClass $user, string $channel);

    /**
     * Get the presence channel members.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @return \React\Promise\PromiseInterface
     */
    public function getChannelMembers($appId, string $channel): PromiseInterface;

    /**
     * Get a member from a presence channel based on connection.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  string  $channel
     * @return \React\Promise\PromiseInterface
     */
    public function getChannelMember(ConnectionInterface $connection, string $channel): PromiseInterface;

    /**
     * Get the presence channels total members count.
     *
     * @param  string|int  $appId
     * @param  array  $channelNames
     * @return \React\Promise\PromiseInterface
     */
    public function getChannelsMembersCount($appId, array $channelNames): PromiseInterface;
}
