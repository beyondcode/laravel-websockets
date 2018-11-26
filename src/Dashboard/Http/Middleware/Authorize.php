<?php

<<<<<<< HEAD:src/Dashboard/Http/Middleware/Authorize.php
namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Middleware;
=======
namespace BeyondCode\LaravelWebSockets\Http\Middleware;
>>>>>>> 224845d6fd754bb8f608af61675ef0519c5d2b1d:src/Http/Middleware/Authorize.php

use Illuminate\Support\Facades\Gate;

class Authorize
{
    public function handle($request, $next)
    {
        return Gate::check('viewWebSocketsDashboard') ? $next($request) : abort(403);
    }
}