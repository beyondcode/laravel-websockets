<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Concerns\PushesToPusher;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Http\Request;
use React\EventLoop\LoopInterface;

use function Clue\React\Block\await;

class AuthenticateDashboard
{
    use PushesToPusher;

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
        $app = await(App::findById($request->header('X-App-Id')), app(LoopInterface::class));

        $broadcaster = $this->getPusherBroadcaster([
            'key' => $app->key,
            'secret' => $app->secret,
            'id' => $app->id,
        ]);

        /*
         * Since the dashboard itself is already secured by the
         * Authorize middleware, we can trust all channel
         * authentication requests in here.
         */
        return $broadcaster->validAuthenticationResponse($request, []);
    }
}
