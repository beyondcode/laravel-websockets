<?php

namespace BeyondCode\LaravelWebSockets\Contracts;

use Amp\Websocket\Client;

interface Connection
{
    /**
     * The underlying client.
     *
     * @return \Amp\Websocket\Client
     */
    public function getClient(): Client;

    /**
     * Returns the connection ID of the client.
     *
     * @return int
     */
    public function getId(): int;

    /**
     * The Application ID its connected to.
     *
     * @return int|string|null
     */
    public function getAppId();
}
