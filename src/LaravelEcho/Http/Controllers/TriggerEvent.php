<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Http\Controllers;

use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Dashboard;
use Illuminate\Http\Request;

class TriggerEvent extends EchoController
{
    public function __invoke(Request $request)
    {
        $this->verifySignature($request);

        foreach ($request->json()->get('channels', []) as $channelId) {
            Dashboard::apiMessage($request->appId, $channelId, $request->json()->get('name'), $request->json()->get('data'));

            $channel = $this->channelManager->find($request->appId, $channelId);

            optional($channel)->broadcastToEveryoneExcept([
                'channel' => $channelId,
                'event' => $request->json()->get('name'),
                'data' => $request->json()->get('data'),
            ], $request->json()->get('socket_id'));
        }

        return $request->json()->all();
    }
}