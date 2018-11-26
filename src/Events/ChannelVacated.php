<?php

namespace BeyondCode\LaravelWebSockets\Events;

class ChannelVacated
{
    /** @var \BeyondCode\LaravelWebSockets\Events\ConnectionInterface */
    protected $connection;

    /** @var string */
    protected $channelId;

    public function __construct(ConnectionInterface $connection, string $channelId)
    {
        $this->connection = $connection;

        $this->channelId = $channelId;
    }
}