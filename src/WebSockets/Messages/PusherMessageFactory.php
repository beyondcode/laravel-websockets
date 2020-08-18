<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Messages;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class PusherMessageFactory
{
    /**
     * Create a new message.
     *
     * @param  \Ratchet\RFC6455\Messaging\MessageInterface  $message
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager  $channelManager
     * @return PusherMessage
     */
    public static function createForMessage(
        MessageInterface $message,
        ConnectionInterface $connection,
        ChannelManager $channelManager): PusherMessage
    {
        $payload = json_decode($message->getPayload());

        return Str::startsWith($payload->event, 'pusher:')
            ? new PusherChannelProtocolMessage($payload, $connection, $channelManager)
            : new PusherClientMessage($payload, $connection, $channelManager);
    }
}
