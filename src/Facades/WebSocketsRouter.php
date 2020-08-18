<?php

namespace BeyondCode\LaravelWebSockets\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see   \BeyondCode\LaravelWebSockets\Server\Router
 * @mixin \BeyondCode\LaravelWebSockets\Server\Router
 */
class WebSocketsRouter extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'websockets.router';
    }
}
