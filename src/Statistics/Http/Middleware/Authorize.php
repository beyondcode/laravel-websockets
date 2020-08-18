<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Http\Middleware;

use BeyondCode\LaravelWebSockets\Apps\App;

class Authorize
{
    /**
     * Authorize the request by app secret.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     */
    public function handle($request, $next)
    {
        return is_null(App::findBySecret($request->secret))
            ? abort(403)
            : $next($request);
    }
}
