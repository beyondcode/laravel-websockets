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
     * @return PromiseInterface[bool]
     */
    public function unsubscribeFromAllChannels(ConnectionInterface $connection): PromiseInterface;

    /**
     * Subscribe the connection to a specific channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  string  $channelName
     * @param  stdClass  $payload
     * @return PromiseInterface[bool]
     */
    public function subscribeToChannel(ConnectionInterface $connection, string $channelName, stdClass $payload): PromiseInterface;

    /**
     * Unsubscribe the connection from the channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  string  $channelName
     * @param  stdClass  $payload
     * @return PromiseInterface[bool]
     */
    public function unsubscribeFromChannel(ConnectionInterface $connection, string $channelName, stdClass $payload): PromiseInterface;

    /**
     * Subscribe the connection to a specific channel, returning
     * a promise containing the amount of connections.
     *
     * @param  string|int  $appId
     * @return PromiseInterface[int]
     */
    public function subscribeToApp($appId): PromiseInterface;

    /**
     * Unsubscribe the connection from the channel, returning
     * a promise containing the amount of connections after decrement.
     *
     * @param  string|int  $appId
     * @return PromiseInterface[int]
     */
    public function unsubscribeFromApp($appId): PromiseInterface;

    /**
     * Get the connections count on the app
     * for the current server instance.
     *
     * @param  string|int  $appId
     * @param  string|null  $channelName
     * @return PromiseInterface[int]
     */
    public function getLocalConnectionsCount($appId, string $channelName = null): PromiseInterface;

    /**
     * Get the connections count
     * across multiple servers.
     *
     * @param  string|int  $appId
     * @param  string|null  $channelName
     * @return PromiseInterface[int]
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
     * @return PromiseInterface[bool]
     */
    public function broadcastAcrossServers($appId, ?string $socketId, string $channel, stdClass $payload, string $serverId = null): PromiseInterface;

    /**
     * Handle the user when it joined a presence channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  stdClass  $user
     * @param  string  $channel
     * @param  stdClass  $payload
     * @return PromiseInterface[bool]
     */
    public function userJoinedPresenceChannel(ConnectionInterface $connection, stdClass $user, string $channel, stdClass $payload): PromiseInterface;

    /**
     * Handle the user when it left a presence channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  stdClass  $user
     * @param  string  $channel
     * @param  stdClass  $payload
     * @return PromiseInterface[bool]
     */
    public function userLeftPresenceChannel(ConnectionInterface $connection, stdClass $user, string $channel): PromiseInterface;

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
     * @return PromiseInterface[bool]
     */
    public function connectionPonged(ConnectionInterface $connection): PromiseInterface;

    /**
     * Remove the obsolete connections that didn't ponged in a while.
     *
     * @return PromiseInterface[bool]
     */
    public function removeObsoleteConnections(): PromiseInterface;
}
