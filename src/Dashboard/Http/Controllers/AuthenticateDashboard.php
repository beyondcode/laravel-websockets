<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use BeyondCode\LaravelWebSockets\Apps\App;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Http\Request;
use Pusher\Pusher;

class AuthenticateDashboard
{
    public function __invoke(Request $request, Broadcaster $broadcaster)
    {
        /**
         * Find the app by using the header
         * and then reconstruct the PusherBroadcaster
         * using our own app selection. 
         */
        $app = App::findById($request->header('x-app-id'));

        $broadcaster->__construct(new Pusher(
            $app->key, $app->secret,
            $app->id, $config['options'] ?? []
        ));

        /*
         * Since the dashboard itself is already secured by the
         * Authorize middleware, we can trust all channel
         * authentication requests in here.
         */
        return $broadcaster->validAuthenticationResponse($request, []);
    }
}
