<?php

namespace BeyondCode\LaravelWebSockets;

use Illuminate\Support\ServiceProvider;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager;

class LaravelWebSocketsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->commands([
            Console\StartWebSocketServer::class,
        ]);
    }

    public function register()
    {
        $this->app->singleton('websockets.router', function() {
            return new Router();
        });

        $this->app->singleton(ChannelManager::class, function() {
            return new ChannelManager();
        });
    }
}
