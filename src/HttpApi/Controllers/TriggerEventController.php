<?php

namespace BeyondCode\LaravelWebSockets\HttpApi\Controllers;

use Illuminate\Http\Request;
use BeyondCode\LaravelWebSockets\HttpApi\Controllers\Concerns\BroadcastsEvents;

class TriggerEventController extends Controller
{
    use BroadcastsEvents;

    public function __invoke(Request $request)
    {
        $this->ensureValidSignature($request);

        foreach ($request->json()->get('channels', []) as $channelName) {
            $this->broadcastEventForAppToChannel(
                $request->appId,
                $channelName,
                $request->json()->get('name'),
                $request->json()->get('data'),
                $request->json()->get('socket_id')
            );
        }

        return $request->json()->all();
    }
}
