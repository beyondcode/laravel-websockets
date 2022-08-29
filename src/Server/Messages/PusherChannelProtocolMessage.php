<?php

namespace BeyondCode\LaravelWebSockets\Server\Messages;

use BeyondCode\LaravelWebSockets\Events\ConnectionPonged;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use stdClass;

class PusherChannelProtocolMessage extends PusherClientMessage
{
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
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    protected function ping(ConnectionInterface $connection)
    {
        $this->channelManager
            ->connectionPonged($connection)
            ->then(function () use ($connection) {
                $connection->send(json_encode(['event' => 'pusher:pong']));

                ConnectionPonged::dispatch($connection->app->id, $connection->socketId);
            });
    }

    /**
     * Subscribe to channel.
     *
     * @see    https://pusher.com/docs/pusher_protocol#pusher-subscribe
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \stdClass  $payload
     * @return void
     */
    protected function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $this->channelManager->subscribeToChannel($connection, $payload->channel, $payload);
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
        $this->channelManager->unsubscribeFromChannel($connection, $payload->channel, $payload);
    }
}
