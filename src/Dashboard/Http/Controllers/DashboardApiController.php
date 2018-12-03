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
                'time' => $statistic->created_at->timestamp,
                'y' => $statistic->peak_connection_count
            ];
        })->reverse()->values();

        return [
            'peak_connections' => $peakConnections
        ];
    }
}