<?php

namespace BeyondCode\LaravelWebSockets\Exceptions;

use Ratchet\WebSocket\MessageComponentInterface;

class InvalidWebSocketController extends \Exception
{
    public static function withController(string $controllerClass)
    {
        $messageComponentInterfaceClass = MessageComponentInterface::class;

        return new static("Invalid WebSocket Controller provided. Expected instance of `{$messageComponentInterfaceClass}`, but received `{$controllerClass}`.");
    }
}
