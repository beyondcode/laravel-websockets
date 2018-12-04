<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels;

use stdClass;
use Ratchet\ConnectionInterface;

class PrivateChannel extends Channel
{
    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $this->verifySignature($connection, $payload);

        parent::subscribe($connection, $payload);
    }
}
