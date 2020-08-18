<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Messages;

interface PusherMessage
{
    /**
     * Respond to the message construction.
     *
     * @return void
     */
    public function respond();
}
