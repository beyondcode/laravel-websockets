<?php

namespace BeyondCode\LaravelWebSockets\HttpApi\Controllers;

use Illuminate\Http\Request;
use BeyondCode\LaravelWebSockets\Statistics\Logger\StatisticsLogger;
use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;

class TriggerEventController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->ensureValidSignature($request);

        foreach ($request->json()->get('channels', []) as $channelName) {
            $channel = $this->channelManager->find($request->appId, $channelName);

            optional($channel)->broadcastToEveryoneExcept([
                'channel' => $channelName,
                'event' => $request->json()->get('name'),
                'data' => $request->json()->get('data'),
            ], $request->json()->get('socket_id'));

            DashboardLogger::apiMessage(
                $request->appId,
                $channelName,
                $request->json()->get('name'),
                $request->json()->get('data')
            );

            app(StatisticsLogger::class)->apiMessage($request->appId);
        }

        return $request->json()->all();
    }
}
