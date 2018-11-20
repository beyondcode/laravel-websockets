<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Http\Controllers;

class StatusController extends EchoController
{

    public function __invoke($request)
    {
        return [
            'subscription_count' => 10
        ];
    }
}