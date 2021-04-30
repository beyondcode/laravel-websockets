<?php

namespace BeyondCode\LaravelWebSockets\Server\Exceptions;

class UnknownAppKey extends WebSocketException
{
    /**
     * Initalize the exception.
     *
     * @param  string  $appKey
     * @return void
     */
    public function __construct($appKey)
    {
        $this->trigger("Could not find app key `{$appKey}`.", 4001);
    }
}
