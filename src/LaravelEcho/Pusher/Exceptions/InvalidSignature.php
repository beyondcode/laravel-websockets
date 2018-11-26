<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Exceptions;

class InvalidSignature extends PusherException
{
    public function __construct()
    {
        $this->message = 'Invalid Signature';

        $this->code = 4009;
    }
}