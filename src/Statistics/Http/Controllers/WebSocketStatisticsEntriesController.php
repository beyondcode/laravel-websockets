<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Http\Controllers;

use BeyondCode\LaravelWebSockets\Statistics\Events\StatisticsUpdated;
use BeyondCode\LaravelWebSockets\Statistics\Rules\AppId;
use Illuminate\Http\Request;

class WebSocketStatisticsEntriesController
{
    public function store(Request $request)
    {
        $validatedAttributes = $request->validate([
            'app_id' => ['required', new AppId()],
            'peak_connection_count' => 'required|integer',
            'websocket_message_count' => 'required|integer',
            'api_message_count' => 'required|integer',
        ]);

        $webSocketsStatisticsEntryModelClass = config('websockets.statistics.model');

        $statisticModel = $webSocketsStatisticsEntryModelClass::create($validatedAttributes);

        broadcast(new StatisticsUpdated($statisticModel));

        return 'ok';
    }
}
