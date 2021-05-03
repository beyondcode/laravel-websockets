<?php

namespace BeyondCode\LaravelWebSockets\Contracts;

use Amp\Promise;
use BeyondCode\LaravelWebSockets\Channels\Channel;

interface ChannelManager
{
    /**
     * Find the channel by app & name.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     *
     * @return \BeyondCode\LaravelWebSockets\Channels\Channel|null
     */
    public function find($appId, string $channel): ?Channel;

    /**
     * Find a channel by app & name or create one.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     *
     * @return \BeyondCode\LaravelWebSockets\Channels\Channel
     */
    public function findOrCreate($appId, string $channel): Channel;

    /**
     * Get the local connections, regardless of the channel
     * they are connected to.
     *
     * @return \Amp\Promise
     */
    public function getLocalConnections(): Promise;

    /**
     * Get all channels for a specific app
     * for the current instance.
     *
     * @param  string|int  $appId
     *
     * @return \Amp\Promise
     */
    public function getLocalChannels($appId): Promise;

    /**
     * Get all channels for a specific app
     * across multiple servers.
     *
     * @param  string|int  $appId
     *
     * @return \Amp\Promise
     */
    public function getGlobalChannels($appId): Promise;

    /**
     * Remove connection from all channels.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     *
     * @return \Amp\Promise
     */
    public function unsubscribeFromAllChannels(Connection $connection): Promise;

    /**
     * Subscribe the connection to a specific channel.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  string  $channelName
     * @param  object|array  $payload
     *
     * @return \Amp\Promise
     */
    public function subscribeToChannel(Connection $connection, string $channelName, $payload): Promise;

    /**
     * Unsubscribe the connection from the channel.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  string  $channelName
     * @param  object|array  $payload
     *
     * @return \Amp\Promise
     */
    public function unsubscribeFromChannel(Connection $connection, string $channelName, $payload): Promise;

    /**
     * Subscribe the connection to a specific channel, returning
     * a promise containing the amount of connections.
     *
     * @param  string|int  $appId
     *
     * @return \Amp\Promise
     */
    public function subscribeToApp($appId): Promise;

    /**
     * Unsubscribe the connection from the channel, returning
     * a promise containing the amount of connections after decrement.
     *
     * @param  string|int  $appId
     *
     * @return \Amp\Promise
     */
    public function unsubscribeFromApp($appId): Promise;

    /**
     * Get the connections count on the app
     * for the current server instance.
     *
     * @param  string|int  $appId
     * @param  string|null  $channelName
     *
     * @return \Amp\Promise
     */
    public function getLocalConnectionsCount($appId, string $channelName = null): Promise;

    /**
     * Get the connections count
     * across multiple servers.
     *
     * @param  string|int  $appId
     * @param  string|null  $channelName
     *
     * @return \Amp\Promise
     */
    public function getGlobalConnectionsCount($appId, string $channelName = null): Promise;

    /**
     * Broadcast the message across multiple servers.
     *
     * @param  string|int  $appId
     * @param  string|null  $socketId
     * @param  string  $channel
     * @param  object|array  $payload
     * @param  string|null  $serverId
     *
     * @return \Amp\Promise
     */
    public function broadcastAcrossServers(
        $appId,
        ?string $socketId,
        string $channel,
        $payload,
        string $serverId = null
    ): Promise;

    /**
     * Handle the user when it joined a presence channel.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  object  $user
     * @param  string  $channel
     * @param  object|array  $payload
     *
     * @return \Amp\Promise [bool]
     */
    public function userJoinedPresenceChannel(Connection $connection, object $user, string $channel, $payload): Promise;

    /**
     * Handle the user when it left a presence channel.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  object  $user
     * @param  string  $channel
     *
     * @return \Amp\Promise
     */
    public function userLeftPresenceChannel(Connection $connection, object $user, string $channel): Promise;

    /**
     * Get the presence channel members.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     *
     * @return \Amp\Promise
     */
    public function getChannelMembers($appId, string $channel): Promise;

    /**
     * Get a member from a presence channel based on connection.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  string  $channel
     *
     * @return \Amp\Promise
     */
    public function getChannelMember(Connection $connection, string $channel): Promise;

    /**
     * Get the presence channels total members count.
     *
     * @param  string|int  $appId
     * @param  array  $channelNames
     *
     * @return \Amp\Promise
     */
    public function getChannelsMembersCount($appId, array $channelNames): Promise;

    /**
     * Get the socket IDs for a presence channel member.
     *
     * @param  string|int  $userId
     * @param  string|int  $appId
     * @param  string  $channelName
     *
     * @return \Amp\Promise<array>
     */
    public function getMemberSockets($userId, $appId, string $channelName): Promise;

    /**
     * Keep tracking the connections availability when they pong.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     *
     * @return \Amp\Promise
     */
    public function connectionPonged(Connection $connection): Promise;

    /**
     * Remove the obsolete connections that didn't ponged in a while.
     *
     * @return \Amp\Promise
     */
    public function removeObsoleteConnections(): Promise;
}
