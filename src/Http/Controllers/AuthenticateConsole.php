<?php

namespace BeyondCode\LaravelWebsockets\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\Broadcasting\Broadcaster;

class AuthenticateConsole
{
    public function __invoke(Request $request, Broadcaster $broadcaster)
    {
        return $broadcaster->validAuthenticationResponse($request, []);
    }
}