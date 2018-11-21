<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels;


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
            /**TODO: make this variable to go away */
            $channelClass = $this->detectChannelClass($channelId);

            $this->channels[$appId][$channelId] = new $channelClass($channelId);
        }

        return $this->channels[$appId][$channelId];
    }

    public function find(string $appId, string $channelId): ?Channel
    {
        return $this->channels[$appId][$channelId] ?? null;
    }

    protected function detectChannelClass($channelId): string
    {
        if (starts_with($channelId, 'private-')) {
            return PrivateChannel::class;
        }

        if (starts_with($channelId, 'presence-')) {
            return PresenceChannel::class;
        }
        return Channel::class;
    }

    public function removeFromAllChannels(ConnectionInterface $connection)
    {
        collect($this->channels[$connection->appId])->each->unsubscribe($connection);

        collect($this->channels[$connection->appId])
            ->reject->hasConnections()
            ->each(function (Channel $channel, string $channelId) use ($connection) {
                unset($this->channels[$connection->appId][$channelId]);
            });

        if (count($this->channels[$connection->appId]) === 0) {
            unset($this->channels[$connection->appId]);
        };
    }
}