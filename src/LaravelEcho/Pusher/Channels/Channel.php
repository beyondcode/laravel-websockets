<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels;

use Illuminate\Support\Collection;
use Ratchet\ConnectionInterface;

class Channel
{
    /** @var string */
    protected $channelId;

    /** @var \Ratchet\ConnectionInterface[] */
    protected $connections = [];

    public function __construct(string $channelId)
    {
        $this->channelId = $channelId;
    }

    public function hasConnections(): bool
    {
        return count($this->connections) > 0;
    }

    /*
     * @link https://pusher.com/docs/pusher_protocol#presence-channel-events
     */
    public function subscribe(ConnectionInterface $connection)
    {
        $this->saveConnection($connection);

        $connection->send(json_encode([
            'event' => 'pusher_internal:subscription_succeeded',
            'channel' => $this->channelId
        ]));
    }

    public function unsubscribe(ConnectionInterface $connection)
    {
        unset($this->connections[$connection->socketId]);
    }

    protected function saveConnection(ConnectionInterface $connection)
    {
        $this->connections[$connection->socketId] = $connection;
    }

    public function broadcast($payload)
    {
        foreach ($this->connections as $connection) {
            $connection->send(json_encode($payload));
        }
    }

    public function broadcastToOthers(ConnectionInterface $connection, $payload)
    {
        Collection::make($this->connections)->reject(function ($connection) use ($connection) {
            return $connection->socketId === $connection->socketId;
        })->each->send(json_encode($payload));
    }
}