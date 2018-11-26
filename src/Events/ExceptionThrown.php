<?php

namespace BeyondCode\LaravelWebSockets\Events;

use Ratchet\ConnectionInterface;

class ExceptionThrown
{
    /** @var \Ratchet\ConnectionInterface */
    public $connection;

    /** @var \Exception */
    public $exception;

    public function __construct(ConnectionInterface $connection, \Exception $exception)
    {
        $this->connection = $connection;

        $this->exception = $exception;
    }
}