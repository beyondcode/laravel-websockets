<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Http\Controllers;

use BeyondCode\LaravelWebSockets\Statistics\Rules\AppId;
use BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry;
use Illuminate\Http\Request;

class WebsocketStatisticsEntriesController
{
    public function store(Request $request)
    {
        $validatedAttributes = $request->validate([
            'app_id' => ['required', new AppId()],
            'peak_connection_count' => 'required|integer',
            'websocket_message_count' => 'required|integer',
            'api_message_count' => 'required|integer',
        ]);

        $webSocketsStatisticsEntryModelClass = config('websockets.statistics_model');

        $webSocketsStatisticsEntryModelClass::create($validatedAttributes);

        return 'ok';
    }
}