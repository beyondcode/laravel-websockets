<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Exceptions;

class OriginNotAllowed extends WebSocketException
{
    /**
     * Initialize the instance.
     *
     * @see    https://pusher.com/docs/pusher_protocol#error-codes
     * @return void
     */
    public function __construct(string $appKey)
    {
        $this->message = "The origin is not allowed for `{$appKey}`.";
        $this->code = 4009;
    }
}
