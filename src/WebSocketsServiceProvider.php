<?php

namespace BeyondCode\LaravelWebSockets;

use BeyondCode\LaravelWebSockets\Apps\AppManager;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\AuthenticateDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\DashboardApiController;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\SendMessage;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\ShowDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware\Authorize as AuthorizeDashboard;
use BeyondCode\LaravelWebSockets\PubSub\Broadcasters\RedisPusherBroadcaster;
use BeyondCode\LaravelWebSockets\Server\Router;
use BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManagers\ArrayChannelManager;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Pusher\Pusher;

class WebSocketsServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/websockets.php' => base_path('config/websockets.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../database/migrations/0000_00_00_000000_create_websockets_statistics_entries_table.php' => database_path('migrations/0000_00_00_000000_create_websockets_statistics_entries_table.php'),
        ], 'migrations');

        $this->registerDashboardRoutes()
            ->registerDashboardGate();

        $this->loadViewsFrom(__DIR__.'/../resources/views/', 'websockets');

        $this->commands([
            Console\StartWebSocketServer::class,
            Console\CleanStatistics::class,
            Console\RestartWebSocketServer::class,
        ]);

        $this->configurePubSub();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/websockets.php', 'websockets');

        $this->app->singleton('websockets.router', function () {
            return new Router();
        });

        $this->app->singleton(ChannelManager::class, function () {
            $channelManager = config('websockets.managers.channel', ArrayChannelManager::class);

            return new $channelManager;
        });

        $this->app->singleton(AppManager::class, function () {
            return $this->app->make(config('websockets.managers.app'));
        });

        $this->app->singleton(StatisticsDriver::class, function () {
            $driver = config('websockets.statistics.driver');

            return $this->app->make(
                config('websockets.statistics')[$driver]['driver']
                ??
                \BeyondCode\LaravelWebSockets\Statistics\Drivers\DatabaseDriver::class
            );
        });
    }

    /**
     * Configure the PubSub replication.
     *
     * @return void
     */
    protected function configurePubSub()
    {
        $this->app->make(BroadcastManager::class)->extend('websockets', function ($app, array $config) {
            $pusher = new Pusher(
                $config['key'], $config['secret'],
                $config['app_id'], $config['options'] ?? []
            );

            if ($config['log'] ?? false) {
                $pusher->setLogger($this->app->make(LoggerInterface::class));
            }

            return new RedisPusherBroadcaster(
                $pusher,
                $config['app_id'],
                $this->app->make('redis'),
                $config['connection'] ?? null
            );
        });
    }

    /**
     * Register the dashboard routes.
     *
     * @return void
     */
    protected function registerDashboardRoutes()
    {
        Route::group([
            'prefix' => config('websockets.dashboard.path'),
            'as' => 'laravel-websockets.',
            'middleware' => config('websockets.dashboard.middleware', [AuthorizeDashboard::class]),
        ], function () {
            Route::get('/', ShowDashboard::class)->name('dashboard');
            Route::get('/api/{appId}/statistics', [DashboardApiController::class, 'getStatistics'])->name('statistics');
            Route::post('/auth', AuthenticateDashboard::class)->name('auth');
            Route::post('/event', SendMessage::class)->name('send');
        });

        return $this;
    }

    /**
     * Register the dashboard gate.
     *
     * @return void
     */
    protected function registerDashboardGate()
    {
        Gate::define('viewWebSocketsDashboard', function ($user = null) {
            return $this->app->environment('local');
        });

        return $this;
    }
}
