<?php

namespace BeyondCode\LaravelWebSockets\Server\Exceptions;

class InvalidAuthData extends WebSocketException
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
        $this->trigger('Invalid auth data', 4302);
    }
}
