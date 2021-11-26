<?php

namespace BeyondCode\LaravelWebSockets\Server\Exceptions;

class ConnectionsOverCapacity extends WebSocketException
{
    /**
     * Initialize the instance.
     *
     * @see    https://pusher.com/docs/pusher_protocol#error-codes
     *
     * @return void
     */
    public function __construct()
    {
        $this->trigger('Over capacity', 4100);
    }
}
