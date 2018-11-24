<?php

namespace BeyondCode\LaravelWebSockets;

use BeyondCode\LaravelWebSockets\ClientProviders\ClientProvider;
use Illuminate\Support\ServiceProvider;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager;

class LaravelWebSocketsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/websockets.php' => base_path('config/websockets.php'),
        ], 'config');

        $this->commands([
            Console\StartWebSocketServer::class,
        ]);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/websockets.php', 'websockets');

        $this->app->singleton('websockets.router', function() {
            return new Router();
        });

        $this->app->singleton(ChannelManager::class, function() {
            return new ChannelManager();
        });

        $this->app->singleton(ClientProvider::class, function() {
            return app(config('websockets.client_provider'));
        });
    }
}
