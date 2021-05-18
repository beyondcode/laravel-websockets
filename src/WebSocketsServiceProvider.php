<?php

namespace BeyondCode\LaravelWebSockets;

use BeyondCode\LaravelWebSockets\Contracts\StatisticsCollector;
use BeyondCode\LaravelWebSockets\Contracts\StatisticsStore;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\AuthenticateDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\SendMessage;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\ShowApps;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\ShowDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\ShowStatistics;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\StoreApp;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware\Authorize as AuthorizeDashboard;
use BeyondCode\LaravelWebSockets\Queue\AsyncRedisConnector;
use BeyondCode\LaravelWebSockets\Server\Router;
use Clue\React\SQLite\DatabaseInterface;
use Clue\React\SQLite\Factory as SQLiteFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\MySQL\ConnectionInterface;
use React\MySQL\Factory as MySQLFactory;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

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

        $this->registerEventLoop();

        $this->registerSQLiteDatabase();

        $this->registerMySqlDatabase();

        $this->registerAsyncRedisQueueDriver();

        $this->registerRouter();

        $this->registerManagers();

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
        //
    }

    protected function registerEventLoop()
    {
        $this->app->singleton(LoopInterface::class, function () {
            return Factory::create();
        });
    }

    /**
     * Register the async, non-blocking Redis queue driver.
     *
     * @return void
     */
    protected function registerAsyncRedisQueueDriver()
    {
        Queue::extend('async-redis', function () {
            return new AsyncRedisConnector($this->app['redis']);
        });
    }

    protected function registerSQLiteDatabase()
    {
        $this->app->singleton(DatabaseInterface::class, function () {
            $factory = new SQLiteFactory($this->app->make(LoopInterface::class));

            $database = $factory->openLazy(
                config('websockets.managers.sqlite.database', ':memory:')
            );

            $migrations = (new Finder())
                ->files()
                ->ignoreDotFiles(true)
                ->in(__DIR__.'/../database/migrations/sqlite')
                ->name('*.sql');

            /** @var SplFileInfo $migration */
            foreach ($migrations as $migration) {
                $database->exec($migration->getContents());
            }

            return $database;
        });
    }

    protected function registerMySqlDatabase()
    {
        $this->app->singleton(ConnectionInterface::class, function () {
            $factory = new MySQLFactory($this->app->make(LoopInterface::class));

            $auth = trim(config('websockets.managers.mysql.username').':'.config('websockets.managers.mysql.password'), ':');
            $connection = trim(config('websockets.managers.mysql.host').':'.config('websockets.managers.mysql.port'), ':');
            $database = config('websockets.managers.mysql.database');

            $database = $factory->createLazyConnection(trim("{$auth}@{$connection}/{$database}", '@'));

            $migrations = (new Finder())
                ->files()
                ->ignoreDotFiles(true)
                ->in(__DIR__.'/../database/migrations/mysql')
                ->name('*.sql');

            /** @var SplFileInfo $migration */
            foreach ($migrations as $migration) {
                $database->query($migration->getContents());
            }

            return $database;
        });
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
            Route::get('/apps', ShowApps::class)->name('apps');
            Route::post('/apps', StoreApp::class)->name('apps.store');
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
