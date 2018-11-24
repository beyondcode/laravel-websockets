<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\WebSocket;

use BeyondCode\LaravelWebSockets\ClientProviders\Client;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Exceptions\PusherException;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Exceptions\UnknownAppKey;
use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use BeyondCode\LaravelWebSockets\WebSocketController;
use BeyondCode\LaravelWebSockets\ClientProviders\Client;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager;
use BeyondCode\LaravelWebsockets\LaravelEcho\Pusher\Exceptions\PusherException;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Exceptions\UnknownAppKeyException;

class PusherServer extends WebSocketController
{
    /** @var \BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager */
    protected $channelManager;

    /** @var ConsoleServer|null */
    protected $consoleServer;

    public function __construct(ChannelManager $channelManager, ConsoleServer $consoleServer = null)
    {
        $this->channelManager = $channelManager;

        $this->consoleServer = $consoleServer;
    }

    function onOpen(ConnectionInterface $connection)
    {
        $this->generateSocketId($connection);

        $this->verifyConnection($connection);

        $this->establishConnection($connection);

        $this->consoleServer->log($connection->appId, 'new_connection', '');
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
        dump($exception);
    }

    protected function verifyConnection(ConnectionInterface $connection)
    {
        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $connection->httpRequest;

        $queryParameters = [];
        parse_str($request->getUri()->getQuery(), $queryParameters);

        if (! $client = Client::findByAppKey($queryParameters['appKey'])) {
            throw new UnknownAppKeyException($queryParameters['appKey']);
        }

        $connection->client = $client;
    }

    protected function establishConnection(ConnectionInterface $connection)
    {
        $connection->send(json_encode([
            'event' => 'pusher:connection_established',
            'data' => json_encode([
                'socket_id' => $connection->socketId,
                'activity_timeout' => 60,
            ])
        ]));
    }

    protected function generateSocketId(ConnectionInterface $connection)
    {
        $socketId = sprintf("%d.%d", getmypid(), random_int(1, 100000000));

        $connection->socketId = $socketId;
    }
}