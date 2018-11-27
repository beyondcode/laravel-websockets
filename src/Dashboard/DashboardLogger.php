<?php

namespace BeyondCode\LaravelWebSockets\Dashboard;

use Ratchet\ConnectionInterface;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use stdClass;

class DashboardLogger
{
    const LOG_CHANNEL_PREFIX = 'private-websockets-dashboard-';
    const TYPE_DISCONNECTION = 'disconnection';
    const TYPE_CONNECTION = 'connection';
    const TYPE_VACATED = 'vacated';
    const TYPE_OCCUPIED = 'occupied';
    const TYPE_SUBSCRIBED = 'subscribed';
    const TYPE_CLIENT_MESSAGE = 'client-message';
    const TYPE_API_MESSAGE = 'api-message';

    public static function connection(ConnectionInterface $connection)
    {
        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $connection->httpRequest;

        static::log($connection->client->appId, static::TYPE_CONNECTION, [
            'details' => "Origin: {$request->getUri()->getScheme()}://{$request->getUri()->getHost()}",
            'socketId' => $connection->socketId,
        ]);
    }

    public static function occupied(ConnectionInterface $connection, string $channelId)
    {
        static::log($connection->client->appId, static::TYPE_OCCUPIED, [
            'details' => "Channel: {$channelId}",
        ]);
    }

    public static function subscribed(ConnectionInterface $connection, string $channelId)
    {
        static::log($connection->client->appId, static::TYPE_SUBSCRIBED, [
            'socketId' => $connection->socketId,
            'details' => "Channel: {$channelId}",
        ]);
    }

    public static function clientMessage(ConnectionInterface $connection, stdClass $payload)
    {
        static::log($connection->client->appId, static::TYPE_CLIENT_MESSAGE, [
            'details' => "Channel: {$payload->channel}, Event: {$payload->event}",
            'socketId' => $connection->socketId,
            'data' => json_encode($payload),
        ]);
    }

    public static function disconnection(ConnectionInterface $connection)
    {
        static::log($connection->client->appId, static::TYPE_DISCONNECTION, [
            'socketId' => $connection->socketId,
        ]);
    }

    public static function vacated(ConnectionInterface $connection, string $channelId)
    {
        static::log($connection->client->appId, static::TYPE_VACATED, [
            'details' => "Channel: {$channelId}",
        ]);
    }

    public static function apiMessage($appId, string $channel, string $event, string $payload)
    {
        static::log($appId, static::TYPE_API_MESSAGE, [
            'details' => "Channel: {$channel}, Event: {$event}",
            'data' => $payload,
        ]);
    }

    public static function log($appId, string $type, array $attributes = [])
    {
        $channelId = static::LOG_CHANNEL_PREFIX . $type;

        $channel = app(ChannelManager::class)->find($appId, $channelId);

        optional($channel)->broadcast([
            'event' => 'log-message',
            'channel' => $channelId,
            'data' => [
                'type' => $type,
                'time' => strftime("%H:%M:%S")
            ] + $attributes,
        ]);
    }

}