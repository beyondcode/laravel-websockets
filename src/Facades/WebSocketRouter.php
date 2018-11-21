<?php

namespace BeyondCode\LaravelWebSockets\Facades;

use Illuminate\Support\Facades\Facade;

/** @see \BeyondCode\LaravelWebSockets\Router */
class WebSocketRouter extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'websockets.router';
    }
}
