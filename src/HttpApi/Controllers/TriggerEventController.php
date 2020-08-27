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

        $channels = $request->channels ?: [];

        foreach ($channels as $channelName) {
            $channel = $this->channelManager->find($request->appId, $channelName);

            $payload = (object) [
                'channel' => $channelName,
                'event' => $request->name,
                'data' => $request->data,
            ];

            optional($channel)->broadcastToEveryoneExcept($payload, $request->socket_id, $request->appId);

            // If the setup is horizontally-scaled using the Redis Pub/Sub,
            // then we're going to make sure it gets streamed to the other
            // servers as well that are subscribed to the Pub/Sub topics
            // attached to the current iterated app & channel.
            // For local setups, the local driver will ignore the publishes.

            $this->replicator->publish($request->appId, $channelName, $payload);

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
