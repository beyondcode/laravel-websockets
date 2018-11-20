<?php

namespace BeyondCode\LaravelWebSockets\Exceptions;

class InvalidWebSocketController extends \Exception
{
    public static function withController($controller)
    {
        return new static('Invalid WebSocket Controller provided. Expected instance of WebSocketController, but received '.$controller);
    }
}