<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels;

use Ratchet\ConnectionInterface;
use stdClass;

class PrivateChannel extends Channel
{
    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $this->verifySignature($connection, $payload);

        parent::subscribe($connection, $payload);
    }
}
