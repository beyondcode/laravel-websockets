<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

class DashboardApiController
{
    public function getStatistics($appId)
    {
        $webSocketsStatisticsEntryModelClass = config('websockets.statistics_model');
        $statistics = $webSocketsStatisticsEntryModelClass::where('app_id', $appId)->latest()->limit(120)->get();

        $peakConnections = $statistics->map(function ($statistic) {
            return [
                'timestamp' => (string)$statistic->created_at,
                'count' => $statistic->peak_connection_count
            ];
        })->reverse();

        return [
            'peak_connections' => [
                'x' => $peakConnections->pluck('timestamp'),
                'y' => $peakConnections->pluck('count'),
            ]
        ];
    }
}