<?php

namespace BeyondCode\LaravelWebsockets\Http\Middleware;

use Illuminate\Support\Facades\Gate;

class Authorize
{
    public function handle($request, $next)
    {
        return Gate::check('viewWebSocketDashboard') ? $next($request) : abort(403);
    }
}