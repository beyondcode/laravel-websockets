<?php

namespace BeyondCode\LaravelWebSockets;

use BeyondCode\LaravelWebSockets\Contracts\ChannelManager;

class DashboardLogger
{
    public const LOG_CHANNEL_PREFIX = 'private-websockets-dashboard-';
    public const TYPE_DISCONNECTED = 'disconnected';
    public const TYPE_CONNECTED = 'connected';
    public const TYPE_SUBSCRIBED = 'subscribed';
    public const TYPE_WS_MESSAGE = 'ws-message';
    public const TYPE_API_MESSAGE = 'api-message';
    public const TYPE_REPLICATOR_SUBSCRIBED = 'replicator-subscribed';
    public const TYPE_REPLICATOR_UNSUBSCRIBED = 'replicator-unsubscribed';
    public const TYPE_REPLICATOR_MESSAGE_RECEIVED = 'replicator-message-received';

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
     * Channel Manager
     *
     * @var \BeyondCode\LaravelWebSockets\Contracts\ChannelManager
     */
    protected $channelManager;

    /**
     * DashboardLogger constructor.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\ChannelManager  $manager
     */
    public function __construct(ChannelManager $manager)
    {
        $this->channelManager = $manager;
    }

    /**
     * Log an event for an app.
     *
     * @param  mixed  $appId
     * @param  string  $type
     * @param  array  $details
     * @return void
     */
    public function log($appId, string $type, array $details = []): void
    {
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

        // Here you can use the ->find(), even if the channel does not exist on
        // the server. If it does not exist, then the message simply will be
        // broadcast across the other servers.
        if ($channel = $this->channelManager->find($appId, $channelName)) {
            $channel->broadcastLocally($appId, $payload);
        }

        $this->channelManager->broadcastAcrossServers($appId, null, $channelName, $payload);
    }
}
