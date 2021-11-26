<?php

namespace BeyondCode\LaravelWebSockets\Channels;

use BeyondCode\LaravelWebSockets\Server\Exceptions\InvalidSignature;
use Ratchet\ConnectionInterface;
use stdClass;

class PrivateChannel extends Channel
{
    /**
     * Subscribe to the channel.
     *
     * @see    https://pusher.com/docs/pusher_protocol#presence-channel-events
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \stdClass  $payload
     * @return bool
     *
     * @throws InvalidSignature
     */
    public function subscribe(ConnectionInterface $connection, stdClass $payload): bool
    {
        $this->verifySignature($connection, $payload);

        return parent::subscribe($connection, $payload);
    }
}
