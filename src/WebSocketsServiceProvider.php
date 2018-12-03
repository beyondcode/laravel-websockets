<?php

namespace BeyondCode\LaravelWebSockets;

use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\AuthenticateDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\SendMessage;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\ShowDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware\Authorize;
use BeyondCode\LaravelWebSockets\Server\Router;
use BeyondCode\LaravelWebSockets\Statistics\Http\Controllers\WebsocketStatisticsEntriesController;
use BeyondCode\LaravelWebSockets\Statistics\Logging\Logger;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use BeyondCode\LaravelWebSockets\Apps\AppProvider;
use Illuminate\Support\ServiceProvider;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;

class WebSocketsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/websockets.php' => base_path('config/websockets.php'),
        ], 'config');

        if (! class_exists('CreateWebSocketsStatisticsEntries')) {
            $this->publishes([
                __DIR__.'/../database/migrations/cretae_websockets_statistics_entries_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_cretae_websockets_statistics_entries_table.php'),
            ], 'migrations');
        }

        $this->registerRouteMacro();

        $this->registerDashboardGate();

        $this->loadViewsFrom(__DIR__.'/../resources/views/', 'websockets');

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

        $this->app->singleton('websockets.statisticslogger', function() {
            return new Logger(app(ChannelManager::class));
        });

        $this->app->singleton(AppProvider::class, function() {
            return app(config('websockets.app_provider'));
        });
    }

    protected function registerRouteMacro()
    {
        Route::macro('webSockets', function($prefix = 'websockets') {
            Route::prefix($prefix)->middleware(Authorize::class)->group(function() {
                Route::get('/',  ShowDashboard::class);
                Route::post('auth', AuthenticateDashboard::class);
                Route::post('event', SendMessage::class);
            });

            //TODO: add middleware
            Route::prefix($prefix)->group(function() {
                Route::post('statistics', [WebsocketStatisticsEntriesController::class, 'store']);
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
