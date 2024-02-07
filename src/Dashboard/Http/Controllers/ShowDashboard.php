<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use BeyondCode\LaravelWebSockets\Contracts\AppManager;
use BeyondCode\LaravelWebSockets\DashboardLogger;
use Illuminate\Http\Request;
use React\EventLoop\LoopInterface;

use function Clue\React\Block\await;

class ShowDashboard
{
    /**
     * Show the dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \BeyondCode\LaravelWebSockets\Contracts\AppManager  $apps
     * @return void
     */
    public function __invoke(Request $request, AppManager $apps)
    {
        return view('websockets::dashboard', [
            'apps' => await($apps->all(), app(LoopInterface::class), 2.0),
            'port' => config('websockets.dashboard.port', 6001),
            'channels' => DashboardLogger::$channels,
            'logPrefix' => DashboardLogger::LOG_CHANNEL_PREFIX,
            'refreshInterval' => config('websockets.statistics.interval_in_seconds'),
        ]);
    }
}
