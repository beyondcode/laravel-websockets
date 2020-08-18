<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Exceptions;

class InvalidSignature extends WebSocketException
{
    /**
     * Initialize the instance.
     *
     * @see    https://pusher.com/docs/pusher_protocol#error-codes
     * @return void
     */
    public function __construct()
    {
        $this->message = 'Invalid Signature';
        $this->code = 4009;
    }
}
