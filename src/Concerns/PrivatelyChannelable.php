<?php

namespace BeyondCode\LaravelWebSockets\Concerns;

use BeyondCode\LaravelWebSockets\Concerns\Channelable;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\InvalidSignature;
use Ratchet\ConnectionInterface;
use stdClass;

trait PrivatelyChannelable
{
    /**
     * Subscribe to the channel.
     *
     * @see    https://pusher.com/docs/pusher_protocol#presence-channel-events
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \stdClass  $payload
     * @return void
     * @throws InvalidSignature
     */
    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $this->verifySignature($connection, $payload);

        parent::subscribe($connection, $payload);
    }
}
