<?php

namespace BeyondCode\LaravelWebSockets\HttpApi\Controllers;

use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\Facades\StatisticsLogger;
use Illuminate\Http\Request;

class TriggerEventController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $this->ensureValidSignature($request);

        foreach ($request->json()->get('channels', []) as $channelName) {
            $channel = $this->channelManager->find($request->appId, $channelName);

            optional($channel)->broadcastToEveryoneExcept((object) [
                'channel' => $channelName,
                'event' => $request->json()->get('name'),
                'data' => $request->json()->get('data'),
            ], $request->json()->get('socket_id'), $request->appId);

            DashboardLogger::log($request->appId, DashboardLogger::TYPE_API_MESSAGE, [
                'channel' => $channelName,
                'event' => $request->json()->get('name'),
                'payload' => $request->json()->get('data'),
            ]);

            StatisticsLogger::apiMessage($request->appId);
        }

        return $request->json()->all();
    }
}
