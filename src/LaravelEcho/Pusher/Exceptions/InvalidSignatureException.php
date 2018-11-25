<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Exceptions;

class InvalidSignatureException extends PusherException
{
    public function __construct()
    {
        $this->message = 'Invalid Signature';

        $this->code = 4009;
    }
}