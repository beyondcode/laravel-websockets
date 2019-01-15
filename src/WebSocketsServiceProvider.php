<?php

namespace BeyondCode\LaravelWebSockets;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use BeyondCode\LaravelWebSockets\Server\Router;
use BeyondCode\LaravelWebSockets\Apps\AppProvider;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\SendMessage;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\ShowDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\AuthenticateDashboard;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers\DashboardApiController;
use BeyondCode\LaravelWebSockets\Database\Http\Controllers\AppsController;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManagers\ArrayChannelManager;
use BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware\Authorize as AuthorizeDashboard;
use BeyondCode\LaravelWebSockets\Database\Http\Middleware\Authorize as AuthorizeAdmin;
use BeyondCode\LaravelWebSockets\Statistics\Http\Middleware\Authorize as AuthorizeStatistics;
use BeyondCode\LaravelWebSockets\Statistics\Http\Controllers\WebSocketStatisticsEntriesController;

class WebSocketsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/websockets.php' => base_path('config/websockets.php'),
        ], 'config');

        $this->publishMigrations();

        $this
            ->registerRoutes()
            ->registerDashboardGate();

        $this->loadViewsFrom(__DIR__.'/../resources/views/', 'websockets');

        $this->commands([
            Console\StartWebSocketServer::class,
            Console\CleanStatistics::class,
            Database\Console\AppCreate::class,
        ]);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/websockets.php', 'websockets');

        $this->app->singleton('websockets.router', function () {
            return new Router();
        });

        $this->app->singleton(ChannelManager::class, function () {
            return config('websockets.channel_manager') !== null && class_exists(config('websockets.channel_manager'))
                ? app(config('websockets.channel_manager')) : new ArrayChannelManager();
        });

        $this->app->singleton(AppProvider::class, function () {
            return app(config('websockets.app_provider'));
        });
    }

    protected function publishMigrations()
    {
        if (! class_exists('CreateWebSocketsStatisticsEntries')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_websockets_statistics_entries_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_websockets_statistics_entries_table.php'),
            ], 'migrations');
        }

        if (! class_exists('CreateWebsocketsAppsTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_websockets_apps_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_websockets_apps_table.php'),
            ], 'migrations');
        }
    }

    protected function registerRoutes()
    {
        Route::prefix(config('websockets.path'))->group(function () {
            Route::middleware(config('websockets.middleware', [AuthorizeDashboard::class]))->group(function () {
                Route::get('/', ShowDashboard::class);
                Route::get('/api/{appId}/statistics', [DashboardApiController::class,  'getStatistics']);
                Route::post('auth', AuthenticateDashboard::class);
                Route::post('event', SendMessage::class);
            });

            Route::middleware(AuthorizeStatistics::class)->group(function () {
                Route::post('statistics', [WebSocketStatisticsEntriesController::class, 'store']);
            });

            Route::middleware(config('websockets.middleware', [AuthorizeAdmin::class]))->group(function () {
                Route::get('/admin', AppsController::class ."@index")->name('websockets.admin.index');
                Route::get('/admin/create', AppsController::class ."@create")->name('websockets.admin.create');
                Route::post('/admin/store', AppsController::class ."@store")->name('websockets.admin.store');
                Route::get('/admin/{app}/edit', AppsController::class ."@edit")->name('websockets.admin.edit');
                Route::post('/admin/{app}/store', AppsController::class ."@update")->name('websockets.admin.update');
                Route::post('/admin/{app}/destroy', AppsController::class ."@destroy")->name('websockets.admin.destroy');
            });
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
