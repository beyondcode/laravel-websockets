<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use BeyondCode\LaravelWebSockets\Apps\App;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Http\Request;
use Pusher\Pusher;

class AuthenticateDashboard
{
    /**
     * Find the app by using the header
     * and then reconstruct the PusherBroadcaster
     * using our own app selection.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $app = App::findById($request->header('x-app-id'));

        $broadcaster = new PusherBroadcaster(new Pusher(
            $app->key,
            $app->secret,
            $app->id,
            []
        ));

        /*
         * Since the dashboard itself is already secured by the
         * Authorize middleware, we can trust all channel
         * authentication requests in here.
         */
        return $broadcaster->validAuthenticationResponse($request, []);
    }
}
