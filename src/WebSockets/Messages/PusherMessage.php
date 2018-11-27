<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Messages;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Ratchet\ConnectionInterface;
use stdClass;

class PusherMessage implements RespondableMessage
{
    /** @var \BeyondCode\LaravelWebSockets\WebSocket\Pusher\stdClass */
    protected $payload;

    /** @var \React\Socket\ConnectionInterface */
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
        $eventName = camel_case(str_after($this->payload->event, ':'));

        if (method_exists($this, $eventName)) {
            call_user_func([$this, $eventName], $this->connection, $this->payload->data ?? new stdClass());
        }
    }

    /*
     * @link https://pusher.com/docs/pusher_protocol#ping-pong
     */
    protected function ping(ConnectionInterface $connection)
    {
        $connection->send(json_encode([
            'event' => 'pusher:pong',
        ]));
    }

    /*
     * @link https://pusher.com/docs/pusher_protocol#pusher-subscribe
     */
    protected function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $channel = $this->channelManager->findOrCreate($connection->client->appId, $payload->channel);

        $channel->subscribe($connection, $payload);
    }

    public function unsubscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $channel = $this->channelManager->findOrCreate($connection->client->appId, $payload->channel);

        $channel->unsubscribe($connection);
    }
}
