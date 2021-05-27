<?php

namespace BeyondCode\LaravelWebSockets\Database\Http\Middleware;

use Illuminate\Support\Facades\Gate;

class Authorize
{
    public function handle($request, $next)
    {
        return Gate::check('viewWebSocketsAdmin', [$request->user()]) ? $next($request) : abort(403);
    }
}
