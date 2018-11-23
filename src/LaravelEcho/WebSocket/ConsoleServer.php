<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\WebSocket;

use Ratchet\ConnectionInterface;

class ConsoleServer extends PusherServer
{
    function onOpen(ConnectionInterface $connection)
    {
        $this->generateSocketId($connection);

        $this->verifyConnection($connection);

        $this->establishConnection($connection);

        // TODO check connection signature
        $connection->isAdmin = true;
    }

    public function log(string $appId, string $type, string $details)
    {
        $channelId = "private-logger-{$type}";

        $channel = $this->channelManager->find($appId, $channelId);

        optional($channel)->broadcast([
            'event' => $type,
            'channel' => $channelId,
            'data' => [
                'type' => $type,
                'details' => $details
            ]
        ]);
    }
}