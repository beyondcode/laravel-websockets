<?php

namespace BeyondCode\LaravelWebSockets\Dashboard;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Ratchet\ConnectionInterface;
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

    const TYPE_REPLICATOR_SUBSCRIBED = 'replicator-subscribed';

    const TYPE_REPLICATOR_UNSUBSCRIBED = 'replicator-unsubscribed';

    public static function connection(ConnectionInterface $connection)
    {
        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $connection->httpRequest;

        static::log($connection->app->id, static::TYPE_CONNECTION, [
            'details' => [
                'origin' => "{$request->getUri()->getScheme()}://{$request->getUri()->getHost()}",
                'socketId' => $connection->socketId,
            ],
        ]);
    }

    public static function occupied(ConnectionInterface $connection, string $channelName)
    {
        static::log($connection->app->id, static::TYPE_OCCUPIED, [
            'details' => [
                'channel' => $channelName,
            ],
        ]);
    }

    public static function subscribed(ConnectionInterface $connection, string $channelName)
    {
        static::log($connection->app->id, static::TYPE_SUBSCRIBED, [
            'details' => [
                'socketId' => $connection->socketId,
                'channel' => $channelName,
            ],
        ]);
    }

    public static function clientMessage(ConnectionInterface $connection, stdClass $payload)
    {
        static::log($connection->app->id, static::TYPE_CLIENT_MESSAGE, [
            'details' => [
                'socketId' => $connection->socketId,
                'channel' => $payload->channel,
                'event' => $payload->event,
                'data' => $payload,
            ],
        ]);
    }

    public static function disconnection(ConnectionInterface $connection)
    {
        static::log($connection->app->id, static::TYPE_DISCONNECTION, [
            'details' => [
                'socketId' => $connection->socketId,
            ],
        ]);
    }

    public static function vacated(ConnectionInterface $connection, string $channelName)
    {
        static::log($connection->app->id, static::TYPE_VACATED, [
            'details' => [
                'socketId' => $connection->socketId,
                'channel' => $channelName,
            ],
        ]);
    }

    public static function apiMessage($appId, string $channel, string $event, string $payload)
    {
        static::log($appId, static::TYPE_API_MESSAGE, [
            'details' => [
                'channel' => $connection,
                'event' => $event,
                'payload' => $payload,
            ],
        ]);
    }

    public static function replicatorSubscribed(string $appId, string $channel, string $serverId)
    {
        static::log($appId, static::TYPE_REPLICATOR_SUBSCRIBED, [
            'details' => [
                'serverId' => $serverId,
                'channel' => $channel,
            ],
        ]);
    }

    public static function replicatorUnsubscribed(string $appId, string $channel, string $serverId)
    {
        static::log($appId, static::TYPE_REPLICATOR_UNSUBSCRIBED, [
            'details' => [
                'serverId' => $serverId,
                'channel' => $channel,
            ],
        ]);
    }

    public static function log($appId, string $type, array $attributes = [])
    {
        $channelName = static::LOG_CHANNEL_PREFIX.$type;

        $channel = app(ChannelManager::class)->find($appId, $channelName);

        optional($channel)->broadcast([
            'event' => 'log-message',
            'channel' => $channelName,
            'data' => [
                'type' => $type,
                'time' => strftime('%H:%M:%S'),
            ] + $attributes,
        ]);
    }
}
