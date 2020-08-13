<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels;

use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\InvalidSignature;
use Ratchet\ConnectionInterface;
use stdClass;

class PrivateChannel extends Channel
{
    /**
     * @throws InvalidSignature
     */
    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $this->verifySignature($connection, $payload);

        parent::subscribe($connection, $payload);
    }
}
