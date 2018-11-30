<?php

namespace BeyondCode\LaravelWebSockets\Facades;

use Illuminate\Support\Facades\Facade;

/** @see \BeyondCode\LaravelWebSockets\Server\Router */
class WebSocketsRouter extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'websockets.router';
    }
}
