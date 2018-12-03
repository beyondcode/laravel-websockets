<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Http\Controllers;

use BeyondCode\LaravelWebSockets\Statistics\WebSocketsStatisticsEntry;
use Illuminate\Http\Request;

class WebsocketStatisticsEntriesController
{
    public function store(Request $request)
    {
        $validatedAttributes = $request->validate([
            'app_id' => 'required',
            'peak_connections' => 'required|integer',
            'websocket_message_count' => 'required|integer',
            'api_message_count' => 'required|integer',
        ]);

        WebSocketsStatisticsEntry::create($validatedAttributes);

        return 'ok';
    }
}