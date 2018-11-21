<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Pusher;

use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\LaravelEcho\WebSocket\RespondableMessage;
use Ratchet\ConnectionInterface;
use stdClass;

class PusherMessage implements RespondableMessage
{
    /** @var \BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\stdClass */
    protected $payload;

    /** @var \React\Socket\ConnectionInterface */
    protected $connection;

    /** @var \BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager */
    protected $channelManager;

    public function __construct(stdClass $payload, ConnectionInterface $connection, ChannelManager $channelManager)
    {
        $this->payload = $payload;

        $this->connection = $connection;

        $this->channelManager = $channelManager;
    }

    public function respond()
    {
        $eventName = camel_case(str_after($this->payload->event, ':'));

        if (method_exists($this, $eventName)) {
            call_user_func([$this, $eventName], $this->connection, $this->payload->data);
        }
    }

    /**
     * @link https://pusher.com/docs/pusher_protocol#ping-pong
     *
     * @param ConnectionInterface $connection
     * @param $payload
     */
    protected function ping(ConnectionInterface $connection, $payload)
    {
        $connection->send(json_encode([
            'event' => 'pusher:pong',
        ]));
    }

    /**
     * @link https://pusher.com/docs/pusher_protocol#pusher-subscribe
     *
     * @param ConnectionInterface $conn
     * @param $payload
     */
    protected function subscribe(ConnectionInterface $connection, $payload)
    {
        $channel = $this->channelManager->findOrCreate($connection->appId, $payload->channel);

        $channel->subscribe($connection, $payload);
    }

    public function unsubscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $channel = $this->channelManager->findOrCreate($connection->appId, $payload->channel);

        $channel->unsubscribe($connection);
    }
}
