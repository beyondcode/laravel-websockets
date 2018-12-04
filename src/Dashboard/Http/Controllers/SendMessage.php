<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use Pusher\Pusher;
use Illuminate\Http\Request;
use BeyondCode\LaravelWebSockets\Statistics\Rules\AppId;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;

class SendMessage
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'appId' => ['required', new AppId()],
            'key' => 'required',
            'secret' => 'required',
            'channel' => 'required',
            'event' => 'required',
            'data' => 'json',
        ]);

        $this->getPusherBroadcaster($validated)->broadcast(
            [$validated['channel']],
            $validated['event'],
            json_decode($validated['data'], true)
        );

        return 'ok';
    }

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
