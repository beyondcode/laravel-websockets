<?php

namespace BeyondCode\LaravelWebSockets;

use BeyondCode\LaravelWebSockets\Apps\AppManager;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\AuthenticateDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\DashboardApiController;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\SendMessage;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\ShowDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware\Authorize as AuthorizeDashboard;
use BeyondCode\LaravelWebSockets\PubSub\Broadcasters\RedisPusherBroadcaster;
use BeyondCode\LaravelWebSockets\PubSub\Drivers\LocalClient;
use BeyondCode\LaravelWebSockets\PubSub\Drivers\RedisClient;
use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;
use BeyondCode\LaravelWebSockets\Server\Router;
use BeyondCode\LaravelWebSockets\Statistics\Http\Controllers\WebSocketStatisticsEntriesController;
use BeyondCode\LaravelWebSockets\Statistics\Http\Middleware\Authorize as AuthorizeStatistics;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManagers\ArrayChannelManager;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Pusher\Pusher;
use React\EventLoop\Factory as LoopFactory;

class WebSocketsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/websockets.php' => base_path('config/websockets.php'),
        ], 'config');

        if (! Schema::hasTable('websockets_statistics_entries')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_websockets_statistics_entries_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_websockets_statistics_entries_table.php'),
            ], 'migrations');
        }

        $this
            ->registerRoutes()
            ->registerDashboardGate();

        $this->loadViewsFrom(__DIR__.'/../resources/views/', 'websockets');

        $this->commands([
            Console\StartWebSocketServer::class,
            Console\CleanStatistics::class,
            Console\RestartWebSocketServer::class,
        ]);

        $this->configurePubSub();
    }

    protected function configurePubSub()
    {
        if (config('websockets.replication.driver', 'local') === 'local') {
            $this->app->singleton(ReplicationInterface::class, function () {
                return new LocalClient;
            });
        }

        if (config('websockets.replication.driver', 'local') === 'redis') {
            $this->app->singleton(ReplicationInterface::class, function () {
                return (new RedisClient)->boot($this->loop ?? LoopFactory::create());
            });
        }

        $this->app->get(BroadcastManager::class)->extend('websockets', function ($app, array $config) {
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
    }

    protected function registerRoutes()
    {
        Route::prefix(config('websockets.dashboard.path'))->group(function () {
            Route::middleware(config('websockets.dashboard.middleware', [AuthorizeDashboard::class]))->group(function () {
                Route::get('/', ShowDashboard::class);
                Route::get('/api/{appId}/statistics', [DashboardApiController::class, 'getStatistics']);
                Route::post('auth', AuthenticateDashboard::class);
                Route::post('event', SendMessage::class);
            });

            Route::middleware(AuthorizeStatistics::class)->group(function () {
                Route::post('statistics', [WebSocketStatisticsEntriesController::class, 'store']);
            });
        });

        return $this;
    }

    protected function registerDashboardGate()
    {
        Gate::define('viewWebSocketsDashboard', function ($user = null) {
            return $this->app->environment('local');
        });

        return $this;
    }
}
