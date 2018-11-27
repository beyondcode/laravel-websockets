<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Pusher\Exceptions;

class UnknownAppKey extends PusherException
{
    public function __construct(string $appKey)
    {
        $this->message = "Could not find app key `{$appKey}`.";

        $this->code = 4001;
    }
}