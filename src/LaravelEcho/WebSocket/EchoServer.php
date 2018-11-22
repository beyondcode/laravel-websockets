<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\WebSocket;

use BeyondCode\LaravelWebsockets\LaravelEcho\Pusher\Exceptions\PusherException;
use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use BeyondCode\LaravelWebSockets\WebSocketController;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager;

class EchoServer extends WebSocketController
{
    /** @var \BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager */
    protected $channelManager;

    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    function onOpen(ConnectionInterface $connection)
    {
        /**
         * There are a couple things we need to do here:
         * 1. Authenticate the incoming request by validating the provided APP-ID is known to us (JSON file lookup?)
         */

        $socketId = sprintf("%d.%d", getmypid(), random_int(1, 100000000));

        // Store the socketId along with the connection so we can retrieve it.
        $connection->socketId = $socketId;

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $connection->httpRequest;

        $queryParameters = [];
        parse_str($request->getUri()->getQuery(), $queryParameters);

        $connection->appId = $queryParameters['appId'];

        $connection->send(json_encode([
            'event' => 'pusher:connection_established',
            'data' => json_encode([
                'socket_id' => $socketId,
                'activity_timeout' => 60,
            ])
        ]));
    }

    public function onMessage(ConnectionInterface $connection, MessageInterface $message)
    {
        $message = RespondableMessageFactory::createForMessage($message, $connection, $this->channelManager);

        $message->respond();
    }

    public function onClose(ConnectionInterface $connection)
    {
        $this->channelManager->removeFromAllChannels($connection);
    }

    function onError(ConnectionInterface $connection, Exception $exception)
    {
        if ($exception instanceof PusherException) {
            $connection->send(json_encode(
                $exception->getPayload()
            ));
        }
    }
}