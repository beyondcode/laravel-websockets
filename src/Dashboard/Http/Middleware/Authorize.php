<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware;

use Illuminate\Support\Facades\Gate;

class Authorize
{
    /**
     * Authorize the current user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     */
    public function handle($request, $next)
    {
        return Gate::check('viewWebSocketsDashboard', [$request->user()])
            ? $next($request)
            : abort(403);
    }
}
