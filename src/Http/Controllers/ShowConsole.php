<?php

namespace BeyondCode\LaravelWebsockets\Http\Controllers;

use Illuminate\Http\Request;
use BeyondCode\LaravelWebSockets\ClientProviders\ClientProvider;

class ShowConsole
{
    public function __invoke(Request $request, ClientProvider $clients)
    {
        return view('websockets::console', [
            'clients' => $clients->all()
        ]);
    }
}