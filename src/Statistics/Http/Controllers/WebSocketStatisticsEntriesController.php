<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\Config\Repository;
use BeyondCode\LaravelWebSockets\Statistics\Rules\AppId;
use BeyondCode\LaravelWebSockets\Statistics\Events\StatisticsUpdated;

class WebSocketStatisticsEntriesController
{
    public function store(Request $request, Repository $config)
    {
        $validatedAttributes = $request->validate([
            'app_id' => ['required', new AppId()],
            'peak_connection_count' => 'required|integer',
            'websocket_message_count' => 'required|integer',
            'api_message_count' => 'required|integer',
        ]);

        $webSocketsStatisticsEntryModelClass = $config->get('websockets.statistics.model');

        $statisticModel = $webSocketsStatisticsEntryModelClass::create($validatedAttributes);

        broadcast(new StatisticsUpdated($statisticModel));

        return 'ok';
    }
}
