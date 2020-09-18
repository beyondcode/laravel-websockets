<?php

namespace BeyondCode\LaravelWebSockets\Contracts;

use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use stdClass;

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
     * Get the local connections, regardless of the channel
     * they are connected to.
     *
     * @return \React\Promise\PromiseInterface
     */
    public function getLocalConnections(): PromiseInterface;

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
     * @param  string|null  $socketId
     * @param  string  $channel
     * @param  stdClass  $payload
     * @param  string|null  $serverId
     * @return bool
     */
    public function broadcastAcrossServers($appId, ?string $socketId, string $channel, stdClass $payload, string $serverId = null);

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

    /**
     * Get the socket IDs for a presence channel member.
     *
     * @param  string|int  $userId
     * @param  string|int  $appId
     * @param  string  $channelName
     * @return \React\Promise\PromiseInterface
     */
    public function getMemberSockets($userId, $appId, $channelName): PromiseInterface;

    /**
     * Keep tracking the connections availability when they pong.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return bool
     */
    public function connectionPonged(ConnectionInterface $connection): bool;

    /**
     * Remove the obsolete connections that didn't ponged in a while.
     *
     * @return bool
     */
    public function removeObsoleteConnections(): bool;
}
