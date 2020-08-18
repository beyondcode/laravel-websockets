<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Http\Controllers;

use BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver;
use BeyondCode\LaravelWebSockets\Statistics\Events\StatisticsUpdated;
use BeyondCode\LaravelWebSockets\Statistics\Rules\AppId;
use Illuminate\Http\Request;

class WebSocketStatisticsEntriesController
{
    /**
     * Store the entry.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver  $driver
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, StatisticsDriver $driver)
    {
        $validatedAttributes = $request->validate([
            'app_id' => ['required', new AppId()],
            'peak_connection_count' => 'required|integer',
            'websocket_message_count' => 'required|integer',
            'api_message_count' => 'required|integer',
        ]);

        broadcast(new StatisticsUpdated(
            $driver::create($validatedAttributes)
        ));

        return 'ok';
    }
}
