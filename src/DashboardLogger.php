<?php

namespace BeyondCode\LaravelWebSockets;

use BeyondCode\LaravelWebSockets\Contracts\ChannelManager;

class DashboardLogger
{
    const LOG_CHANNEL_PREFIX = 'private-websockets-dashboard-';

    const TYPE_DISCONNECTED = 'disconnected';

    const TYPE_CONNECTED = 'connected';

    const TYPE_SUBSCRIBED = 'subscribed';

    const TYPE_WS_MESSAGE = 'ws-message';

    const TYPE_API_MESSAGE = 'api-message';

    const TYPE_REPLICATOR_SUBSCRIBED = 'replicator-subscribed';

    const TYPE_REPLICATOR_UNSUBSCRIBED = 'replicator-unsubscribed';

    const TYPE_REPLICATOR_MESSAGE_RECEIVED = 'replicator-message-received';

    /**
     * The list of all channels.
     *
     * @var array
     */
    public static $channels = [
        self::TYPE_DISCONNECTED,
        self::TYPE_CONNECTED,
        self::TYPE_SUBSCRIBED,
        self::TYPE_WS_MESSAGE,
        self::TYPE_API_MESSAGE,
        self::TYPE_REPLICATOR_SUBSCRIBED,
        self::TYPE_REPLICATOR_UNSUBSCRIBED,
        self::TYPE_REPLICATOR_MESSAGE_RECEIVED,
    ];

    /**
     * Log an event for an app.
     *
     * @param  mixed  $appId
     * @param  string  $type
     * @param  array  $details
     * @return void
     */
    public static function log($appId, string $type, array $details = [])
    {
        $channelManager = app(ChannelManager::class);

        $channelName = static::LOG_CHANNEL_PREFIX.$type;

        $payload = [
            'event' => 'log-message',
            'channel' => $channelName,
            'data' => [
                'type' => $type,
                'time' => strftime('%H:%M:%S'),
                'details' => $details,
            ],
        ];

        // Here you can use the ->find(), even if the channel
        // does not exist on the server. If it does not exist,
        // then the message simply will get broadcasted
        // across the other servers.
        $channel = $channelManager->find($appId, $channelName);

        if ($channel) {
            $channel->broadcastLocally(
                $appId, (object) $payload
            );
        }

        $channelManager->broadcastAcrossServers(
            $appId, null, $channelName, (object) $payload
        );
    }
}
