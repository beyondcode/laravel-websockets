<?php

namespace BeyondCode\LaravelWebSockets\Facades;

use Illuminate\Support\Facades\Facade;

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
