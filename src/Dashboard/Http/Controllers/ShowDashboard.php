<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use BeyondCode\LaravelWebSockets\Apps\AppManager;
use Illuminate\Http\Request;

class ShowDashboard
{
    public function __invoke(Request $request, AppManager $apps)
    {
        return view('websockets::dashboard', [
            'apps' => $apps->all(),
            'port' => config('websockets.dashboard.port', 6001),
        ]);
    }
}
