<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use BeyondCode\LaravelWebSockets\Facades\StatisticsStore;
use Illuminate\Http\Request;
use Beekman\Crud\Services\BroadcastChannels;

class ShowChannels
{
    /**
     * Get channels for an app ID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $appId
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, $appId)
    {
        return BroadcastChannels::all();
    }
}
