<?php

namespace BeyondCode\LaravelWebSockets\WebSocketServer\Controllers;

use BeyondCode\LaravelWebSockets\Events\ApiMessageSent;
use Illuminate\Http\Request;

class TriggerEvent extends EchoController
{
    public function __invoke(Request $request)
    {
        $this->ensureValidSignature($request);

        foreach ($request->json()->get('channels', []) as $channelId) {
            $channel = $this->channelManager->find($request->appId, $channelId);

            optional($channel)->broadcastToEveryoneExcept([
                'channel' => $channelId,
                'event' => $request->json()->get('name'),
                'data' => $request->json()->get('data'),
            ], $request->json()->get('socket_id'));

            event(new ApiMessageSent(
                $request->appId,
                $channelId,
                $request->json()->get('name'),
                $request->json()->get('data')
            ));
        }

        return $request->json()->all();
    }
}