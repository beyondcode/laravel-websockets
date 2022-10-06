<?php

namespace BeyondCode\LaravelWebSockets\API;

use BeyondCode\LaravelWebSockets\DashboardLogger;
use BeyondCode\LaravelWebSockets\Facades\StatisticsCollector;
use Illuminate\Http\Request;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class TriggerEvent extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return PromiseInterface
     */
    public function __invoke(Request $request)
    {
        if ($request->has('channel')) {
            $channels = [$request->get('channel')];
        } else {
            $channels = $request->channels ?: [];

            if (is_string($channels)) {
                $channels = [$channels];
            }
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
                'event' => $request->name,
                'channel' => $channelName,
                'data' => $request->data,
            ];

            if ($channel) {
                $channel->broadcastLocallyToEveryoneExcept(
                    (object) $payload,
                    $request->socket_id,
                    $request->appId
                );
            }

            $this->channelManager->broadcastAcrossServers(
                $request->appId, $request->socket_id, $channelName, (object) $payload
            );

            $deferred = new Deferred();

            $this->ensureValidAppId($request->appId)
                ->then(function ($app) use ($request, $channelName, $deferred) {
                    if ($app->statisticsEnabled) {
                        StatisticsCollector::apiMessage($request->appId);
                    }

                    DashboardLogger::log($request->appId, DashboardLogger::TYPE_API_MESSAGE, [
                        'event' => $request->name,
                        'channel' => $channelName,
                        'payload' => $request->data,
                    ]);

                    $deferred->resolve((object) []);
                });
        }

        return $deferred->promise();
    }
}
