<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Exceptions;

class InvalidConnectionException extends PusherException
{
    public function __construct()
    {
        $this->message = 'Invalid Connection';
        $this->code = 4009;
    }
}