<?php

namespace BeyondCode\LaravelWebsockets\Dashboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\Broadcasting\Broadcaster;

class AuthenticateDashboard
{
    public function __invoke(Request $request, Broadcaster $broadcaster)
    {
        /*
         * Since the dashboard itself is already secured by the
         * Authorize middleware, we can trust all channel
         * authentication requests in here.
         */
        return $broadcaster->validAuthenticationResponse($request, []);
    }
}