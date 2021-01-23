<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Http\Middleware;

use BeyondCode\LaravelWebSockets\Apps\App;

class Authorize
{
    public function handle($request, $next)
    {
        $app = App::findByKey($request->key);

        return is_null($app) || $app->secret !== $request->secret
            ? abort(403)
            : $next($request);
    }
}
