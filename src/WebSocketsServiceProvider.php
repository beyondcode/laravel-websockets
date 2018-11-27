<?php

namespace BeyondCode\LaravelWebSockets;

use BeyondCode\LaravelWebSockets\Dashboard\EventSubscriber;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\AuthenticateDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\SendMessage;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\ShowDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware\Authorize;
use BeyondCode\LaravelWebSockets\Server\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use BeyondCode\LaravelWebSockets\ClientProviders\ClientProvider;
use Illuminate\Support\ServiceProvider;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;

class WebSocketsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/websockets.php' => base_path('config/websockets.php'),
        ], 'config');

        $this->registerRouteMacro();

        $this->registerDashboardGate();

        $this->loadViewsFrom(__DIR__.'/../resources/views/', 'websockets');

        $this->commands([
            Console\StartWebSocketServer::class,
        ]);

        Event::subscribe(EventSubscriber::class);
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

    protected function registerRouteMacro()
    {
        Route::macro('webSocketsDashboard', function($prefix = 'websockets') {
            Route::prefix($prefix)->namespace('\\')->middleware(Authorize::class)->group(function() {
                Route::get('/',  ShowDashboard::class);
                Route::post('auth', AuthenticateDashboard::class);
                Route::post('event', SendMessage::class);
            });
        });
    }

    protected function registerDashboardGate()
    {
        Gate::define('viewWebSocketsDashboard', function ($user = null) {
            return app()->environment('local');
        });
    }
}
