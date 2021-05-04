<?php

namespace BeyondCode\LaravelWebSockets\Contracts;

use BeyondCode\LaravelWebSockets\Channels\Channel;

interface ChannelManager
{
    /**
     * Find the channel by app & name.
     *
     * @param  string
     * @param  string  $channel
     *
     * @return \BeyondCode\LaravelWebSockets\Channels\Channel|null
     */
    public function find(string $appId, string $channel): ?Channel;

    /**
     * Find a channel by app & name or create one.
     *
     * @param  string  $appId
     * @param  string  $channel
     *
     * @return \BeyondCode\LaravelWebSockets\Channels\Channel
     */
    public function findOrCreate(string $appId, string $channel): Channel;

    /**
     * Get the local connections, regardless of the channel they are connected to.
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<\Illuminate\Support\Collection<\BeyondCode\LaravelWebSockets\Contracts\Client>  An
     *     array containing all
     */
    public function getLocalClients(): Promise;

    /**
     * Get all channels for a specific app for the current instance.
     *
     * @param  string  $appId
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<array<\BeyondCode\LaravelWebSockets\Channels\Channel>>
     */
    public function getLocalChannels(string $appId): Promise;

    /**
     * Get all channels for a specific app across multiple servers.
     *
     * @param  string  $appId
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<\Illuminate\Support\Collection<\BeyondCode\LaravelWebSockets\Channels\Channel>>
     */
    public function getGlobalChannels(string $appId): Promise;

    /**
     * Unsubscribe a Client from all channels.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Client  $client
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<\Illuminate\Support\Collection<\BeyondCode\LaravelWebSockets\Channels\Channel>>  Returns
     *     all the channels unsubscribed from.
     */
    public function unsubscribeFromAllChannels(Client $client): Promise;

    /**
     * Subscribe a Client to a specific channel, with an optional message after it's done.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Client  $client
     * @param  string  $channelName
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Message|null  $message
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<bool> Returns false if the client was already subscribed
     *     to the channel.
     */
    public function subscribeToChannel(Client $client, string $channelName, Message $message = null): Promise;

    /**
     * Unsubscribe the connection from the channel.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Client  $client
     * @param  string  $channelName
     * @param  object|array  $payload
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<bool> Returns false if the client was not subscribed to
     *     the channel.
     */
    public function unsubscribeFromChannel(Client $client, string $channelName, $payload): Promise;

    /**
     * Subscribe the Server connection to a specific application channel.
     *
     * @param  string  $appId
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<int> Amount of connections of the app channel.
     */
    public function subscribeToApp(string $appId): Promise;

    /**
     * Unsubscribe the Server connection from the specific application channel.
     *
     * @param  string  $appId
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<int> Amount of connections of the app channel.
     */
    public function unsubscribeFromApp(string $appId): Promise;

    /**
     * Get the Clients count on the app for the current server instance.
     *
     * @param  string  $appId
     * @param  string|null  $channelName
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<int> The count
     */
    public function getLocalClientsCount(string $appId, string $channelName = null): Promise;

    /**
     * Get the connections count across multiple servers.
     *
     * @param  string  $appId
     * @param  string|null  $channelName
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<int> The count
     */
    public function getGlobalClientsCount(string $appId, string $channelName = null): Promise;

    /**
     * Broadcast a message across multiple servers.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Message  $message
     * @param  string  $appId
     * @param  string  $channel
     * @param  string|null  $serverId
     * @param  string|null  $clientId
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise
     */
    public function broadcastAcrossServers(
        Message $message,
        string $appId,
        string $channel,
        string $serverId = null,
        string $clientId = null
    ): Promise;

    /**
     * Handle the Client after it joins a presence channel.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Client  $client
     * @param  object  $user
     * @param  string  $channel
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Message|null  $message
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<bool>  If the user joined the presence channel
     *     successfully.
     */
    public function userJoinedPresenceChannel(
        Client $client,
        object $user,
        string $channel,
        Message $message = null
    ): Promise;

    /**
     * Handle the user after it left a presence channel.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Client  $client
     * @param  object  $user
     * @param  string  $channel
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Message|null  $message
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<bool>  If the user left the presence channel
     *     successfully.
     */
    public function userLeftPresenceChannel(
        Client $client,
        object $user,
        string $channel,
        Message $message = null
    ): Promise;

    /**
     * Get the presence channel Clients.
     *
     * @param  string  $appId
     * @param  string  $channel
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<\Illuminate\Support\Collection<array>>
     */
    public function getChannelMembers(string $appId, string $channel): Promise;

    /**
     * Get a member from a presence channel based on Client information.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Client  $client
     * @param  string  $channel
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<array|null>
     */
    public function getChannelMember(Client $client, string $channel): Promise;

    /**
     * Get the presence channels total members count.
     *
     * @param  string  $appId
     * @param  array  $channelNames
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<int> The count of members.
     */
    public function getChannelsMembersCount(string $appId, array $channelNames): Promise;

    /**
     * Get the Client IDs for a presence channel member.
     *
     * @param  string|int  $userId
     * @param  string|int  $appId
     * @param  string  $channelName
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<array>
     */
    public function getMemberSockets($userId, $appId, string $channelName): Promise;

    /**
     * Keep tracking the connections availability when they pong.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Client  $client
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<bool>  If the connection was successfully ponged.
     */
    public function connectionPonged(Client $client): Promise;

    /**
     * Remove the obsolete connections that didn't ponged in a while.
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<int> The number of connections removed
     */
    public function removeObsoleteConnections(): Promise;
}
