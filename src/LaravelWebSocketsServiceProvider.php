<?php

namespace BeyondCode\LaravelWebSockets;

use Illuminate\Support\ServiceProvider;

class LaravelWebSocketsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
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
