<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use BeyondCode\LaravelWebSockets\Concerns\PushesToPusher;
use BeyondCode\LaravelWebSockets\Rules\AppId;
use Illuminate\Http\Request;
use Throwable;

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
            'event' => 'required|string',
            'channel' => 'required|string',
            'data' => 'required|json',
        ]);

        $broadcaster = $this->getPusherBroadcaster([
            'key' => $request->key,
            'secret' => $request->secret,
            'id' => $request->appId,
        ]);

        try {
            $decodedData = json_decode($request->data, true);

            $broadcaster->broadcast(
                [$request->channel],
                $request->event,
                $decodedData ?: []
            );
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'exception' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'ok' => true,
        ]);
    }
}
