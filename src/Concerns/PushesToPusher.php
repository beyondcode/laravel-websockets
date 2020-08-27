<?php

namespace BeyondCode\LaravelWebSockets\Concerns;

use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Pusher\Pusher;

trait PushesToPusher
{
    /**
     * Get the right Pusher broadcaster for the used driver.
     *
     * @param  array  $app
     * @return \Illuminate\Broadcasting\Broadcasters\Broadcaster
     */
    public function getPusherBroadcaster(array $app)
    {
        return new PusherBroadcaster(
            new Pusher(
                $app['key'],
                $app['secret'],
                $app['id'],
                config('broadcasting.connections.pusher.options', [])
            )
        );
    }
}
