<?php

namespace BeyondCode\LaravelWebSockets;

use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\AuthenticateDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\DashboardApiController;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\SendMessage;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\ShowDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware\Authorize;
use BeyondCode\LaravelWebSockets\Server\Router;
use BeyondCode\LaravelWebSockets\Statistics\Http\Controllers\WebSocketStatisticsEntriesController;
use BeyondCode\LaravelWebSockets\Statistics\Logger\HttpStatisticsLogger;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use BeyondCode\LaravelWebSockets\Apps\AppProvider;
use Illuminate\Support\ServiceProvider;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Illuminate\Support\Str;

class WebSocketsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/websockets.php' => base_path('config/websockets.php'),
        ], 'config');

        if (!class_exists('CreateWebSocketsStatisticsEntries')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_websockets_statistics_entries_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_websockets_statistics_entries_table.php'),
            ], 'migrations');
        }

        $this
            ->registerRoutes()
            ->registerDashboardGate();

        $this->loadViewsFrom(__DIR__ . '/../resources/views/', 'websockets');

        $this->commands([
            Console\StartWebSocketServer::class,
        ]);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/websockets.php', 'websockets');

        $this->app->singleton('websockets.router', function () {
            return new Router();
        });

        $this->app->singleton(ChannelManager::class, function () {
            return new ChannelManager();
        });

        $this->app->singleton(AppProvider::class, function () {
            return app(config('websockets.app_provider'));
        });
    }

    protected function registerRoutes()
    {
        Route::prefix(config('websockets.path'))->middleware(Authorize::class)->group(function () {
            Route::get('/', ShowDashboard::class);
            Route::get('/api/{appId}/statistics', [DashboardApiController::class,  'getStatistics']);
            Route::post('auth', AuthenticateDashboard::class);
            Route::post('event', SendMessage::class);

            Route::post('statistics', [WebSocketStatisticsEntriesController::class, 'store']);
        });

        return $this;
    }

    protected function registerDashboardGate()
    {
        Gate::define('viewWebSocketsDashboard', function ($user = null) {
            return app()->environment('local');
        });

        return $this;
    }
}
