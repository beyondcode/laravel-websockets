<?php

namespace BeyondCode\LaravelWebSockets;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router as LaravelRouter;
use BeyondCode\LaravelWebSockets\Facades\WebSocketRouter;

class LaravelWebSocketsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        LaravelRouter::macro('websocket', function($uri, $action) {
            WebSocketRouter::addRoute($uri, $action);
        });

        $this->commands([
            Console\StartWebSocketServer::class,
        ]);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->singleton('websockets.router', function() {
            return new Router();
        });
    }
}
