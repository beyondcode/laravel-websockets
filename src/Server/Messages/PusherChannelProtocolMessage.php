<?php

namespace BeyondCode\LaravelWebSockets\Server\Messages;

use BeyondCode\LaravelWebSockets\Contracts\Connection;
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
    public function respond(): void
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
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     *
     * @return void
     */
    protected function ping(Connection $connection): void
    {
        $this->channelManager
            ->connectionPonged($connection)
            ->onResolve(static function () use ($connection): void {
                $connection->getClient()->send(json_encode(['event' => 'pusher:pong']));

                ConnectionPonged::dispatch($connection->getAppId(), $connection->getId());
            });
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
