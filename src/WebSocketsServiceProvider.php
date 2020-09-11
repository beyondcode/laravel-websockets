<?php

namespace BeyondCode\LaravelWebSockets;

use BeyondCode\LaravelWebSockets\Contracts\StatisticsCollector;
use BeyondCode\LaravelWebSockets\Contracts\StatisticsStore;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\AuthenticateDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\SendMessage;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\ShowDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\ShowStatistics;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware\Authorize as AuthorizeDashboard;
use BeyondCode\LaravelWebSockets\Server\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

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
            __DIR__.'/../config/websockets.php' => config_path('websockets.php'),
        ], 'config');

        $this->mergeConfigFrom(
            __DIR__.'/../config/websockets.php', 'websockets'
        );

        $this->publishes([
            __DIR__.'/../database/migrations/0000_00_00_000000_create_websockets_statistics_entries_table.php' => database_path('migrations/0000_00_00_000000_create_websockets_statistics_entries_table.php'),
            __DIR__.'/../database/migrations/0000_00_00_000000_rename_statistics_counters.php' => database_path('migrations/0000_00_00_000000_rename_statistics_counters.php'),
        ], 'migrations');

        $this->registerStatistics();

        $this->registerDashboard();

        $this->registerCommands();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRouter();
        $this->registerManagers();
    }

    /**
     * Register the statistics-related contracts.
     *
     * @return void
     */
    protected function registerStatistics()
    {
        $this->app->singleton(StatisticsStore::class, function () {
            $class = config('websockets.statistics.store');

            return new $class;
        });

        $this->app->singleton(StatisticsCollector::class, function () {
            $replicationMode = config('websockets.replication.mode', 'local');

            $class = config("websockets.replication.modes.{$replicationMode}.collector");

            return new $class;
        });
    }

    /**
     * Regsiter the dashboard components.
     *
     * @return void
     */
    protected function registerDashboard()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views/', 'websockets');

        $this->registerDashboardRoutes();
        $this->registerDashboardGate();
    }

    /**
     * Register the package commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands([
            Console\Commands\StartServer::class,
            Console\Commands\RestartServer::class,
            Console\Commands\CleanStatistics::class,
            Console\Commands\FlushCollectedStatistics::class,
        ]);
    }

    /**
     * Register the routing.
     *
     * @return void
     */
    protected function registerRouter()
    {
        $this->app->singleton('websockets.router', function () {
            return new Router;
        });
    }

    /**
     * Register the managers for the app.
     *
     * @return void
     */
    protected function registerManagers()
    {
        $this->app->singleton(Contracts\AppManager::class, function () {
            return $this->app->make(config('websockets.managers.app'));
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
            Route::get('/api/{appId}/statistics', ShowStatistics::class)->name('statistics');
            Route::post('/auth', AuthenticateDashboard::class)->name('auth');
            Route::post('/event', SendMessage::class)->name('event');
        });
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
    }
}
