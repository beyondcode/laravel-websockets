<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\WebSocket;

use BeyondCode\LaravelWebSockets\Events\ConnectionEstablished;
use BeyondCode\LaravelWebSockets\QueryParameters;
use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use BeyondCode\LaravelWebSockets\WebSocketController;
use BeyondCode\LaravelWebSockets\ClientProviders\Client;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Exceptions\PusherException;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Exceptions\UnknownAppKey;

class PusherServer extends WebSocketController
{
    /** @var \BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager */
    protected $channelManager;

    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    function onOpen(ConnectionInterface $connection)
    {
        $this
            ->generateSocketId($connection)
            ->verifyConnection($connection)
            ->establishConnection($connection);
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

    public function onError(ConnectionInterface $connection, Exception $exception)
    {
        if ($exception instanceof PusherException) {
            $connection->send(json_encode(
                $exception->getPayload()
            ));
        }
    }

    protected function generateSocketId(ConnectionInterface $connection)
    {
        $socketId = sprintf("%d.%d", getmypid(), random_int(1, 100000000));

        $connection->socketId = $socketId;

        return $this;
    }

    protected function verifyConnection(ConnectionInterface $connection)
    {
        $appKey = QueryParameters::create($connection->httpRequest)->get('appKey');

        if (!$client = Client::findByAppKey($appKey)) {
            throw new UnknownAppKey($appKey);
        }

        $connection->client = $client;

        return $this;
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

        event(new ConnectionEstablished($connection));

        return $this;
    }
}