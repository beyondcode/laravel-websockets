<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use BeyondCode\LaravelWebSockets\Contracts\AppManager;
use BeyondCode\LaravelWebSockets\DashboardLogger;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ShowDashboard
{
    /**
     * Show the dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \BeyondCode\LaravelWebSockets\Contracts\AppManager  $apps
     * @param  \Illuminate\Contracts\Config\Repository  $config
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request, AppManager $apps, Repository $config): View
    {
        return view('websockets::dashboard', [
            'apps' => $apps->all(),
            'port' => $config->get('websockets.dashboard.port', 6001),
            'channels' => DashboardLogger::$channels,
            'logPrefix' => DashboardLogger::LOG_CHANNEL_PREFIX,
            'refreshInterval' => $config->get('websockets.statistics.interval_in_seconds'),
        ]);
    }
}
