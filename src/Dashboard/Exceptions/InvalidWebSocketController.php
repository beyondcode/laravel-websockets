<?php

namespace BeyondCode\LaravelWebSockets\Exceptions;

use Exception;
use Ratchet\WebSocket\MessageComponentInterface;

class InvalidWebSocketController extends Exception
{
    /**
     * Allocate a controller to the error.
     *
     * @param  string  $controllerClass
     * @return \BeyondCode\LaravelWebSockets\Exceptions\InvalidWebSocketController
     */
    public static function withController(string $controllerClass)
    {
        $class = MessageComponentInterface::class;

        return new static(
            "Invalid WebSocket Controller provided. Expected instance of `{$class}`, but received `{$controllerClass}`."
        );
    }
}
