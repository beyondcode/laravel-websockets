<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Exceptions;

class ConnectionsOverCapacity extends WebSocketException
{
    /**
     * Initialize the instance.
     *
     * @see    https://pusher.com/docs/pusher_protocol#error-codes
     * @return void
     */
    public function __construct()
    {
        $this->message = 'Over capacity';
        $this->code = 4100;
    }
}
