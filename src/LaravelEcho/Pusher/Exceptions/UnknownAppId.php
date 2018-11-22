<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Exceptions;

class UnknownAppId extends PusherException
{
    public function __construct(string $appId)
    {
        $this->message = "Could not find app key {$appId}";
        $this->code = 4001;
    }
}