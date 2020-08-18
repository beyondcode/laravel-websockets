<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use BeyondCode\LaravelWebSockets\Statistics\Rules\AppId;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Http\Request;
use Pusher\Pusher;

class SendMessage
{
    /**
     * Send the message to the requested channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'appId' => ['required', new AppId],
            'key' => 'required|string',
            'secret' => 'required|string',
            'channel' => 'required|string',
            'event' => 'required|string',
            'data' => 'required|json',
        ]);

        $this->getPusherBroadcaster($validated)->broadcast(
            [$validated['channel']],
            $validated['event'],
            json_decode($validated['data'], true)
        );

        return 'ok';
    }

    /**
     * Get the pusher broadcaster for the current request.
     *
     * @param  array  $validated
     * @return \Illuminate\Broadcasting\Broadcasters\PusherBroadcaster
     */
    protected function getPusherBroadcaster(array $validated): PusherBroadcaster
    {
        $pusher = new Pusher(
            $validated['key'],
            $validated['secret'],
            $validated['appId'],
            config('broadcasting.connections.pusher.options', [])
        );

        return new PusherBroadcaster($pusher);
    }
}
