<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels;

use Ratchet\ConnectionInterface;

interface ChannelManager
{
    /**
     * Find a channel by name or create one.
     *
     * @param  mixed  $appId
     * @param  string  $channelName
     * @return \BeyondCode\LaravelWebSockets\WebSockets\Channels\Channel
     */
    public function findOrCreate($appId, string $channelName): Channel;

    /**
     * Find a channel by name.
     *
     * @param  mixed  $appId
     * @param  string  $channelName
     * @return \BeyondCode\LaravelWebSockets\WebSockets\Channels\Channel
     */
    public function find($appId, string $channelName): ?Channel;

    /**
     * Get all channels.
     *
     * @param  mixed  $appId
     * @return array
     */
    public function getChannels($appId): array;

    /**
     * Get the connections count on the app.
     *
     * @param  mixed  $appId
     * @return int
     */
    public function getLocalConnectionsCount($appId): int;

    /**
     * Get the connections count across multiple servers.
     *
     * @param  mixed  $appId
     * @return int
     */
    public function getGlobalConnectionsCount($appId): int;

    /**
     * Remove connection from all channels.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function removeFromAllChannels(ConnectionInterface $connection);
}
