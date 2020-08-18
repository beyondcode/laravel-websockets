<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Messages;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use stdClass;

class PusherChannelProtocolMessage implements PusherMessage
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
     * Respond with the payload.
     *
     * @return void
     */
    public function respond()
    {
        $eventName = Str::camel(Str::after($this->payload->event, ':'));

        if (method_exists($this, $eventName) && $eventName !== 'respond') {
            call_user_func([$this, $eventName], $this->connection, $this->payload->data ?? new stdClass());
        }
    }

    /**
     * Ping the connection.
     *
     * @see    https://pusher.com/docs/pusher_protocol#ping-pong
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    protected function ping(ConnectionInterface $connection)
    {
        $connection->send(json_encode([
            'event' => 'pusher:pong',
        ]));
    }

    /**
     * Subscribe to channel.
     *
     * @see    https://pusher.com/docs/pusher_protocol#pusher-subscribe
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \stdClass  $payload
     * @return void
     */
    protected function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $channel = $this->channelManager->findOrCreate($connection->app->id, $payload->channel);

        $channel->subscribe($connection, $payload);
    }

    /**
     * Unsubscribe from the channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \stdClass  $payload
     * @return void
     */
    public function unsubscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $channel = $this->channelManager->findOrCreate($connection->app->id, $payload->channel);

        $channel->unsubscribe($connection);
    }
}
