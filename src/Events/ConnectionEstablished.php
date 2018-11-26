<?php

namespace BeyondCode\LaravelWebSockets\Events;

use Ratchet\ConnectionInterface;
use stdClass;

class ConnectionEstablished
{
    /** @var \Ratchet\ConnectionInterface */
    public $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }
}