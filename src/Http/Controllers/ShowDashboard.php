<?php

namespace BeyondCode\LaravelWebSockets\Http\Controllers;

use Illuminate\Http\Request;
use BeyondCode\LaravelWebSockets\ClientProviders\ClientProvider;

class ShowDashboard
{
    public function __invoke(Request $request, ClientProvider $clients)
    {
        return view('websockets::dashboard', [
            'clients' => $clients->all()
        ]);
    }
}