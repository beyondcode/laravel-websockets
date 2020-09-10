<?php

namespace BeyondCode\LaravelWebSockets\Server\Exceptions;

class OriginNotAllowed extends WebSocketException
{
    /**
     * Initalize the exception.
     *
     * @param  string  $appKey
     * @return void
     */
    public function __construct($appKey)
    {
        $this->trigger("The origin is not allowed for `{$appKey}`.", 4009);
    }
}
