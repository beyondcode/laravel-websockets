<?php

namespace BeyondCode\LaravelWebSockets\Contracts;

interface ClientMessage extends Message
{
    /**
     * Returns the Client that sent the message.
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Client
     */
    public function getClient(): Client;
}
