<?php

namespace BeyondCode\LaravelWebSockets\Events;

use Ratchet\ConnectionInterface;

class ChannelVacated
{
    /** @var \Ratchet\ConnectionInterface */
    public $connection;

    /** @var string */
    public $channelId;

    public function __construct(ConnectionInterface $connection, string $channelId)
    {
        $this->connection = $connection;

        $this->channelId = $channelId;
    }
}