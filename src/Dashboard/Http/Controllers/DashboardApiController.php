<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

class DashboardApiController
{
    public function getStatistics($appId)
    {
        $webSocketsStatisticsEntryModelClass = config('websockets.statistics.model');
        $statistics = $webSocketsStatisticsEntryModelClass::where('app_id', $appId)->latest()->limit(120)->get();

        $statisticData = $statistics->map(function ($statistic) {
            return [
                'timestamp' => (string) $statistic->created_at,
                'peak_connection_count' => $statistic->peak_connection_count,
                'websocket_message_count' => $statistic->websocket_message_count,
                'api_message_count' => $statistic->api_message_count,
            ];
        })->reverse();

        return [
            'peak_connections' => [
                'x' => $statisticData->pluck('timestamp'),
                'y' => $statisticData->pluck('peak_connection_count'),
            ],
            'websocket_message_count' => [
                'x' => $statisticData->pluck('timestamp'),
                'y' => $statisticData->pluck('websocket_message_count'),
            ],
            'api_message_count' => [
                'x' => $statisticData->pluck('timestamp'),
                'y' => $statisticData->pluck('api_message_count'),
            ],
        ];
    }
}
