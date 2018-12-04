<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Exceptions;

class InvalidConnection extends WebSocketException
{
    public function __construct()
    {
        $this->message = 'Invalid Connection';

        $this->code = 4009;
    }
}
