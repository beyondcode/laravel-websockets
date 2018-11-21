<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels;

use Ratchet\ConnectionInterface;

class Channel
{
    protected $channelId;


    public function __construct($channelId)
    {
        $this->channelId = $channelId;
    }

    public function subscribe(ConnectionInterface $conn, $payload)
    {

    }
}