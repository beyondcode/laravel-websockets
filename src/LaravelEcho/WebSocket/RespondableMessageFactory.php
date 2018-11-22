<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\WebSocket;

use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\PusherMessage;
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

        // Log this for now
        dump($payload);

        return starts_with($payload->event, 'pusher:')
            ? new PusherMessage($payload, $connection, $channelManager)
            : new Message($payload, $connection, $channelManager);
    }
}