<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use Illuminate\Http\Request;
use BeyondCode\LaravelWebSockets\Apps\AppProvider;

class ShowDashboard
{
    public function __invoke(Request $request, AppProvider $clients)
    {
        return view('websockets::dashboard', [
            'clients' => $clients->all(),
        ]);
    }
}