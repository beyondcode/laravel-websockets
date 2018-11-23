<?php

namespace BeyondCode\LaravelWebSockets;

use Illuminate\Support\Facades\Route;
use BeyondCode\LaravelWebSockets\ClientProviders\ClientProvider;
use Illuminate\Support\ServiceProvider;
use BeyondCode\LaravelWebSockets\LaravelEcho\WebSocket\ConsoleServer;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager;

class LaravelWebSocketsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/websockets.php' => base_path('config/websockets.php'),
        ], 'config');

        $this->registerRoutes();

        $this->loadViewsFrom(__DIR__.'/../resources/views/', 'websockets');

        $this->commands([
            Console\StartWebSocketServer::class,
        ]);
    }

    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
        });
    }

    protected function routeConfiguration()
    {
        return [
            'namespace' => 'BeyondCode\LaravelWebSockets\Http\Controllers',
            'prefix' => config('websockets.path'),
        ];
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

        $this->app->singleton(ConsoleServer::class, function() {
            return new ConsoleServer(new ChannelManager());
        });
    }
}
