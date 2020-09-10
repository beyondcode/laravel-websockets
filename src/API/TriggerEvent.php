<?php

namespace BeyondCode\LaravelWebSockets\API;

use BeyondCode\LaravelWebSockets\DashboardLogger;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\PresenceChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;
use BeyondCode\LaravelWebSockets\Facades\StatisticsCollector;

class TriggerEvent extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $channels = $request->channels ?: [];

        if (is_string($channels)) {
            $channels = [$channels];
        }

        foreach ($channels as $channelName) {
            // Here you can use the ->find(), even if the channel
            // does not exist on the server. If it does not exist,
            // then the message simply will get broadcasted
            // across the other servers.
            $channel = $this->channelManager->find(
                $request->appId, $channelName
            );

            $payload = [
                'channel' => $channelName,
                'event' => $request->name,
                'data' => $request->data,
            ];

            if ($channel) {
                $channel->broadcastToEveryoneExcept(
                    (object) $payload,
                    $request->socket_id,
                    $request->appId
                );
            } else {
                $this->channelManager->broadcastAcrossServers(
                    $request->appId, $channelName, (object) $payload
                );
            }

            StatisticsCollector::apiMessage($request->appId);

            DashboardLogger::log($request->appId, DashboardLogger::TYPE_API_MESSAGE, [
                'channel' => $channelName,
                'event' => $request->name,
                'payload' => $request->data,
            ]);
        }

        return $request->json()->all();
    }
}
