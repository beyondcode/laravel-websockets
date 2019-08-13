<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Exceptions;

class ConnectionsOverCapacity extends WebSocketException
{
    public function __construct()
    {
        $this->message = 'Over capacity';

        // @See https://pusher.com/docs/pusher_protocol#error-codes
        // Indicates an error resulting in the connection
        // being closed by Pusher, and that the client may reconnect after 1s or more.
        $this->code = 4100;
    }
}
