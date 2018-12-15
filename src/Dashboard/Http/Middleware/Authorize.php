<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware;

use Illuminate\Contracts\Auth\Access\Gate;

class Authorize
{
    public function handle($request, $next)
    {
        return app(Gate::class)->check('viewWebSocketsDashboard') ? $next($request) : abort(403);
    }
}
