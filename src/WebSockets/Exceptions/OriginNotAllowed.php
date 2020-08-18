<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Exceptions;

class OriginNotAllowed extends WebSocketException
{
    public function __construct(string $appKey)
    {
        $this->message = "The origin is not allowed for `{$appKey}`.";
        $this->code = 4009;
    }
}
