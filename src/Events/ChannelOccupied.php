<?php

namespace BeyondCode\LaravelWebSockets\Events;

use Ratchet\ConnectionInterface;

class ChannelOccupied
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