<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels;

use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Exceptions\InvalidConnectionException;
use stdClass;
use Ratchet\ConnectionInterface;

class LoggingChannel extends Channel
{
    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $this->verifySignature($connection, $payload);

        $this->verifyAdministrator($connection);

        parent::subscribe($connection, $payload);
    }

    protected function verifyAdministrator(ConnectionInterface $connection)
    {
        throw_unless($connection->isAdmin, new InvalidConnectionException());
    }
}