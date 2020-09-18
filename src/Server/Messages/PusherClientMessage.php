<?php

namespace BeyondCode\LaravelWebSockets\Server\Messages;

use BeyondCode\LaravelWebSockets\Contracts\ChannelManager;
use BeyondCode\LaravelWebSockets\Contracts\PusherMessage;
use BeyondCode\LaravelWebSockets\DashboardLogger;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use stdClass;

class PusherClientMessage implements PusherMessage
{
    /**
     * The payload to send.
     *
     * @var \stdClass
     */
    protected $payload;

    /**
     * The socket connection.
     *
     * @var \Ratchet\ConnectionInterface
     */
    protected $connection;

    /**
     * The channel manager.
     *
     * @var ChannelManager
     */
    protected $channelManager;

    /**
     * Create a new instance.
     *
     * @param  \stdClass  $payload
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  ChannelManager  $channelManager
     */
    public function __construct(stdClass $payload, ConnectionInterface $connection, ChannelManager $channelManager)
    {
        $this->payload = $payload;
        $this->connection = $connection;
        $this->channelManager = $channelManager;
    }

    /**
     * Respond to the message construction.
     *
     * @return void
     */
    public function respond()
    {
        if (! Str::startsWith($this->payload->event, 'client-')) {
            return;
        }

        if (! $this->connection->app->clientMessagesEnabled) {
            return;
        }

        $channel = $this->channelManager->find(
            $this->connection->app->id, $this->payload->channel
        );

        optional($channel)->broadcastToEveryoneExcept(
            $this->payload, $this->connection->socketId, $this->connection->app->id
        );

        DashboardLogger::log($this->connection->app->id, DashboardLogger::TYPE_WS_MESSAGE, [
            'socketId' => $this->connection->socketId,
            'event' => $this->payload->event,
            'channel' => $this->payload->channel,
            'data' => $this->payload,
        ]);
    }
}
