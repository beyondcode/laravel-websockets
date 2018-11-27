<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Pusher\Exceptions;

class InvalidConnection extends PusherException
{
    public function __construct()
    {
        $this->message = 'Invalid Connection';

        $this->code = 4009;
    }
}