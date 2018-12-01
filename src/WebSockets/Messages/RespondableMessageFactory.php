<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Messages;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class RespondableMessageFactory
{
    public static function createForMessage(
        MessageInterface $message,
        ConnectionInterface $connection,
        ChannelManager $channelManager): RespondableMessage
    {
        $payload = json_decode($message->getPayload());

        return starts_with($payload->event, 'pusher:')
            ? new PusherChannelProtocolMessage($payload, $connection, $channelManager)
            : new PusherClientMessage($payload, $connection, $channelManager);
    }
}