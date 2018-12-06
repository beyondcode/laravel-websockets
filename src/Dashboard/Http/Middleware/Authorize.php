<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware;

use Illuminate\Support\Facades\Gate;

class Authorize
{
    public function handle($request, $next)
    {
        return Gate::check('viewWebSocketsDashboard', [$request->user()]) ? $next($request) : abort(403);
    }
}
