<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver;
use Illuminate\Http\Request;

class ShowStatistics
{
    /**
     * Get statistics for an app ID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver  $driver
     * @param  mixed  $appId
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, StatisticsDriver $driver, $appId)
    {
        return $driver::get($appId, $request);
    }
}
