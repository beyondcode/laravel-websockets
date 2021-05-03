<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use BeyondCode\LaravelWebSockets\Concerns\PushesToPusher;
use BeyondCode\LaravelWebSockets\Rules\AppId;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class SendMessage
{
    use PushesToPusher;

    /**
     * Send the message to the requested channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \BeyondCode\LaravelWebSockets\Rules\AppId  $rule
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, AppId $rule): JsonResponse
    {
        $request->validate([
            'appId' => ['required', $rule],
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
            $broadcaster->broadcast(
                [$request->channel],
                $request->event,
                json_decode($request->data, true) ?: []
            );
        } catch (Throwable $exception) {
            return response()->json(['ok' => false, 'exception' => $exception->getMessage()]);
        }

        return response()->json(['ok' => true]);
    }
}
