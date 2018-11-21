<?php

namespace BeyondCode\LaravelWebSockets\Exceptions;

use BeyondCode\LaravelWebSockets\WebSocketController;

class InvalidWebSocketController extends \Exception
{
    public static function withController(string $controllerClass)
    {
        $websocketControllerClass = WebSocketController::class;

        return new static("Invalid WebSocket Controller provided. Expected instance of `{$websocketControllerClass}`, but received `{$controllerClass}`.");
    }
}