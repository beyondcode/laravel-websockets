<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels;

use Ratchet\ConnectionInterface;

class ChannelManager
{
    /** @var string */
    protected $appId;

    /** @var array */
    protected $channels = [];

    public function findOrCreate(string $appId, string $channelName): Channel
    {
        if (!isset($this->channels[$appId][$channelName])) {
            $channelClass = $this->determineChannelClass($channelName);

            $this->channels[$appId][$channelName] = new $channelClass($channelName);
        }

        return $this->channels[$appId][$channelName];
    }

    public function find(string $appId, string $channelName): ?Channel
    {
        return $this->channels[$appId][$channelName] ?? null;
    }

    protected function determineChannelClass(string $channelName): string
    {
        if (starts_with($channelName, 'private-')) {
            return PrivateChannel::class;
        }

        if (starts_with($channelName, 'presence-')) {
            return PresenceChannel::class;
        }

        return Channel::class;
    }

    public function getChannels(string $appId): array
    {
        return $this->channels[$appId] ?? [];
    }

    public function removeFromAllChannels(ConnectionInterface $connection)
    {
        if (!isset($connection->app)) {
            return;
        }

        /**
         * Remove the connection from all channels.
         */
        collect(array_get($this->channels, $connection->app->id, []))->each->unsubscribe($connection);

        /**
         * Unset all channels that have no connections so we don't leak memory.
         */
        collect(array_get($this->channels, $connection->app->id, []))
            ->reject->hasConnections()
            ->each(function (Channel $channel, string $channelName) use ($connection) {
                unset($this->channels[$connection->app->id][$channelName]);
            });

        if (count(array_get($this->channels, $connection->app->id, [])) === 0) {
            unset($this->channels[$connection->app->id]);
        };
    }
}