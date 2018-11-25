<?php

namespace BeyondCode\LaravelWebSockets;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class WebSocketController implements MessageComponentInterface
{
    function onOpen(ConnectionInterface $connection)
    {
    }

    public function onMessage(ConnectionInterface $connection, MessageInterface $message)
    {
    }

    function onClose(ConnectionInterface $connection)
    {
    }
    
    function onError(ConnectionInterface $connection, Exception $exception)
    {
    }
}