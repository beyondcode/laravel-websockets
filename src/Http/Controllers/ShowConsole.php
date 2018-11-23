<?php

namespace BeyondCode\LaravelWebsockets\Http\Controllers;

use Illuminate\Http\Request;

class ShowConsole
{
    public function __invoke(Request $request)
    {
        return view('websockets::console');
    }
}