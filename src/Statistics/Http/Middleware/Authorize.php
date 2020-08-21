<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Http\Middleware;

use BeyondCode\LaravelWebSockets\Apps\App;

class Authorize
{
    public function handle($request, $next)
    {
        $app = App::findBySecret($request->secret);
        if (is_null($app) || $app->id != $request->app_id) {
            return abort(403);
        }
    
        return $next($request);
    }
}
