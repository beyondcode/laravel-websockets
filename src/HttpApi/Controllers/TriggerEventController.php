<?php

namespace BeyondCode\LaravelWebSockets\HttpApi\Controllers;

use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use Illuminate\Http\Request;

class TriggerEventController extends Controller
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

            DashboardLogger::apiMessage(
                $request->appId,
                $channelId,
                $request->json()->get('name'),
                $request->json()->get('data')
            );
        }

        return $request->json()->all();
    }
}