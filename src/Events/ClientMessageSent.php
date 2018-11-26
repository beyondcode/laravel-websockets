<?php

namespace BeyondCode\LaravelWebSockets\Events;

use Ratchet\ConnectionInterface;
use stdClass;

class ClientMessageSent
{
    /** @var \Ratchet\ConnectionInterface */
    public $connection;

    /** @var string */
    public $payload;

    public function __construct(ConnectionInterface $connection, stdClass $payload)
    {
        $this->connection = $connection;

        $this->payload = $payload;
    }
}