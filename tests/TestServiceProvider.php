<?php

namespace BeyondCode\LaravelWebSockets\Tests;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        Gate::define('viewWebSocketsDashboard', function ($user = null) {
            return ! is_null($user);
        });
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
}
