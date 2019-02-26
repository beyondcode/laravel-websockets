<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Messages;

use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;

class PusherMessageFactory
{
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
