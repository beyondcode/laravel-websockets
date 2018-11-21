<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Http\Controllers;

use Illuminate\Http\Request;

class EventController extends EchoController
{

    public function __invoke(Request $request)
    {
        return $request->json()->all();
    }
}