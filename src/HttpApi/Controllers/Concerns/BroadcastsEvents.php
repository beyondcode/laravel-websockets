<?php

namespace BeyondCode\LaravelWebSockets\HttpApi\Controllers\Concerns;

trait BroadcastsEvents
{
    protected function broadcastEventForAppToChannel($appId, $channelName, $event, $data, $socketId = null): void
    {
        $channel = $this->channelManager->find($appId, $channelName);

        optional($channel)->broadcastToEveryoneExcept([
            'channel' => $channelName,
            'event' => $event,
            'data' => $data,
        ], $socketId);

        DashboardLogger::apiMessage(
            $appId,
            $channelName,
            $event,
            $data
        );

        StatisticsLogger::apiMessage($appId);
    }
}
