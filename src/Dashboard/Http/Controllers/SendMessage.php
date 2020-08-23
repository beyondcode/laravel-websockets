<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use BeyondCode\LaravelWebSockets\Contracts\PushesToPusher;
use BeyondCode\LaravelWebSockets\Statistics\Rules\AppId;
use Illuminate\Http\Request;

class SendMessage
{
    use PushesToPusher;

    /**
     * Send the message to the requested channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'appId' => ['required', new AppId],
            'key' => 'required|string',
            'secret' => 'required|string',
            'channel' => 'required|string',
            'event' => 'required|string',
            'data' => 'required|json',
        ]);

        $broadcaster = $this->getPusherBroadcaster([
            'key' => $request->key,
            'secret' => $request->secret,
            'id' => $request->appId,
        ]);

        $broadcaster->broadcast(
            [$request->channel],
            $request->event,
            json_decode($request->data, true)
        );

        return 'ok';
    }
}
