<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Pusher\Exceptions;

class InvalidSignature extends PusherException
{
    public function __construct()
    {
        $this->message = 'Invalid Signature';

        $this->code = 4009;
    }
}