<?php

namespace BeyondCode\LaravelWebSockets\Test\Handlers;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class TestHandler implements MessageComponentInterface
{
    public function onOpen(ConnectionInterface $connection)
    {
        $connection->close();
    }

    public function onClose(ConnectionInterface $connection)
    {
        //
    }

    public function onError(ConnectionInterface $connection, Exception $e)
    {
        dump($e->getMessage());
    }

    public function onMessage(ConnectionInterface $connection, MessageInterface $msg)
    {
        dump($msg);
    }
}
