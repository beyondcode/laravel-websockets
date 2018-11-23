<?php

namespace BeyondCode\LaravelWebsockets\Http\Controllers;

use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Http\Request;

class AuthenticateConsole
{
    public function __invoke(Request $request, Broadcaster $broadcaster)
    {
        return $broadcaster->validAuthenticationResponse($request, []);
    }
}