<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels;

use Ratchet\ConnectionInterface;

class ChannelManager
{
    /** @var array */
    protected $channels = [];

    /** @var string */
    protected $appId;

    public function findOrCreate(string $appId, string $channelId): Channel
    {
        if (!isset($this->channels[$appId][$channelId])) {
            $channelClass = $this->determineChannelClass($channelId);
            
            $this->channels[$appId][$channelId] = new $channelClass($channelId);
        }

        return $this->channels[$appId][$channelId];
    }

    public function find(string $appId, string $channelId): ?Channel
    {
        return $this->channels[$appId][$channelId] ?? null;
    }

    protected function determineChannelClass(string $channelId): string
    {
        if (starts_with($channelId, 'private-')) {
            return PrivateChannel::class;
        }

        if (starts_with($channelId, 'presence-')) {
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
        if (! isset($connection->client)) {
            return;
        }

        /**
         * Remove the connection from all channels.
         */
        collect(array_get($this->channels, $connection->client->appId, []))->each->unsubscribe($connection);

        /**
         * Unset all channels that have no connections so we don't leak memory.
         */
        collect(array_get($this->channels, $connection->client->appId, []))
            ->reject->hasConnections()
            ->each(function (Channel $channel, string $channelId) use ($connection) {
                unset($this->channels[$connection->client->appId][$channelId]);
            });

        if (count(array_get($this->channels, $connection->client->appId, [])) === 0) {
            unset($this->channels[$connection->client->appId]);
        };
    }
}