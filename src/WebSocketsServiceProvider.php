<?php

namespace BeyondCode\LaravelWebSockets;

use BeyondCode\LaravelWebSockets\Contracts\ChannelManager;
use BeyondCode\LaravelWebSockets\Contracts\Loop;
use BeyondCode\LaravelWebSockets\Contracts\StatisticsCollector;
use BeyondCode\LaravelWebSockets\Contracts\StatisticsStore;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\AuthenticateDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\SendMessage;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\ShowDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\ShowStatistics;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware\Authorize as AuthorizeDashboard;
use BeyondCode\LaravelWebSockets\Loop\EventLoopManager;
use BeyondCode\LaravelWebSockets\Queue\AsyncRedisConnector;
use BeyondCode\LaravelWebSockets\Server\Router;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Container\Container;
use Illuminate\Queue\QueueManager;
use Illuminate\Routing\Router as LaravelRouter;
use Illuminate\Support\ServiceProvider;

class WebSocketsServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/websockets.php', 'websockets'
        );

        $this->app->singleton(Loop::class, static function (Container $app): Loop {
            return $app[EventLoopManager::class]->driver();
        });

        $this->app->singleton('loop.manager', EventLoopManager::class);

        $this->registerAsyncRedisQueueDriver();

        $this->registerRouter();

        $this->registerManagers();

        $this->registerStatistics();
    }

    /**
     * Boot the service provider.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     *
     * @return void
     */
    public function boot(LaravelRouter $router, Gate $gate): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/websockets.php' => config_path('websockets.php')], 'config');
            $this->publishes([
                __DIR__.'/../database/migrations/0000_00_00_000000_create_websockets_statistics_entries_table.php' => database_path('migrations/0000_00_00_000000_create_websockets_statistics_entries_table.php'),
            ], 'migrations');

            $this->registerCommands();
        }

        $this->bootDashboard($router, $gate);
    }

    /**
     * Register the async, non-blocking Redis queue driver.
     *
     * @return void
     */
    protected function registerAsyncRedisQueueDriver(): void
    {
        $this->app->extend('queue', function (QueueManager $queue, Container $app): QueueManager {
            $queue->extend('async-redis', static function () use ($app): AsyncRedisConnector {
                return new AsyncRedisConnector($app['redis']);
            });

            return $queue;
        });
    }

    /**
     * Register the package commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        $this->commands([
            Console\Commands\StartServer::class,
            Console\Commands\RestartServer::class,
            Console\Commands\CleanStatistics::class,
            Console\Commands\FlushCollectedStatistics::class,
        ]);
    }

    /**
     * Register the statistics-related contracts.
     *
     * @return void
     */
    protected function registerStatistics(): void
    {
        $this->app->singleton(StatisticsStore::class, static function ($app): StatisticsStore {
            $config = $app['config']['websockets'];
            $class = $config['statistics']['store'];

            return new $class($app[ChannelManager::class]);
        });

        $this->app->singleton(StatisticsCollector::class, function ($app): StatisticsCollector {
            $config = $app['config']['websockets'];
            $replicationMode = $config['replication']['mode'] ?? 'local';

            $class = $config['replication']['modes'][$replicationMode]['collector'];

            return new $class($app[ChannelManager::class]);
        });
    }

    /**
     * Register the routing.
     *
     * @return void
     */
    protected function registerRouter(): void
    {
        $this->app->singleton('websockets.router', function () {
            return new Router();
        });
    }

    /**
     * Register the managers for the app.
     *
     * @return void
     */
    protected function registerManagers(): void
    {
        $this->app->singleton(Contracts\ChannelManager::class, function ($app) {
            $config = $app['config']['websockets'];
            $channelManager = $config['replication']['mode'] ?? 'local';

            $class = $config['replication']['modes'][$channelManager]['channel_manager'];

            return new $class;
        });

        $this->app->singleton(Contracts\AppManager::class, function ($app) {
            $config = $app['config']['websockets'];

            return $this->app->make($config['managers']['app']);
        });
    }

    /**
     * Register the dashboard components.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     *
     * @return void
     */
    protected function bootDashboard(LaravelRouter $router, Gate $gate): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views/', 'websockets');

        $this->bootDashboardRoutes($router);
        $this->bootDashboardGate($gate);
    }

    /**
     * Register the dashboard routes.
     *
     * @param  \Illuminate\Routing\Router  $router
     *
     * @return void
     */
    protected function bootDashboardRoutes(LaravelRouter $router): void
    {
        $router->group([
            'domain' => config('websockets.dashboard.domain'),
            'prefix' => config('websockets.dashboard.path'),
            'as' => 'laravel-websockets.',
//            'middleware' => config('websockets.dashboard.middleware', [AuthorizeDashboard::class]),
        ], function (LaravelRouter $router) {
            $router->get('/', ShowDashboard::class)->name('dashboard');
            $router->get('/api/{appId}/statistics', ShowStatistics::class)->name('statistics');
            $router->post('/auth', AuthenticateDashboard::class)->name('auth');
            $router->post('/event', SendMessage::class)->name('event');
        });
    }

    /**
     * Register the dashboard gate.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     *
     * @return void
     */
    protected function bootDashboardGate(Gate $gate): void
    {
        $gate->define('view websockets dashboard', function () {
            return $this->app->environment('local');
        });
    }
}
