<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Messages;

use BeyondCode\LaravelWebSockets\Events\ClientMessageSent;
use BeyondCode\LaravelWebSockets\WebSocket\Pusher\Channels\ChannelManager;
use Ratchet\ConnectionInterface;
use stdClass;

class Message implements RespondableMessage
{
    /** \stdClass */
    protected $payload;

    /** @var \Ratchet\ConnectionInterface */
    protected $connection;

    /** @var \BeyondCode\LaravelWebSockets\WebSocket\Pusher\Channels\ChannelManager */
    protected $channelManager;

    public function __construct(stdClass $payload, ConnectionInterface $connection, ChannelManager $channelManager)
    {
        $this->payload = $payload;

        $this->connection = $connection;

        $this->channelManager = $channelManager;
    }

    public function respond()
    {
        if (!starts_with($this->payload->event, 'client-')) {
            return;
        }

        event(new ClientMessageSent($this->connection, $this->payload));

        $channel = $this->channelManager->find($this->connection->client->appId, $this->payload->channel);

        optional($channel)->broadcast($this->payload);
    }
}