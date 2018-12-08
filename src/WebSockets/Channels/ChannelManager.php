<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels;

use Ratchet\ConnectionInterface;

interface ChannelManager
{
    public function findOrCreate(string $appId, string $channelName): Channel;

    public function find(string $appId, string $channelName): ?Channel;

    public function getChannels(string $appId): array;

    public function getConnectionCount(string $appId): int;

    public function removeFromAllChannels(ConnectionInterface $connection);
}
