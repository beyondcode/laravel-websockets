<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Http\Controllers;

use Illuminate\Http\Request;

class StatusController extends EchoController
{
    public function __invoke(Request $request)
    {
        return [
            'subscription_count' => 10
        ];
    }
}