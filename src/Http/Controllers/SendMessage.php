<?php

namespace BeyondCode\LaravelWebsockets\Http\Controllers;

use Pusher\Pusher;
use Illuminate\Http\Request;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;

class SendMessage
{
    public function __invoke(Request $request)
    {
        $pusher = new Pusher(
            $request->key,
            $request->secret,
            $request->appId,
            config('broadcasting.connections.pusher.options', [])
        );

        return (new PusherBroadcaster($pusher))
            ->broadcast([$request->channel], $request->event, json_decode($request->data, true));
    }
}