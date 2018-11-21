<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels;

use Illuminate\Support\Collection;
use Ratchet\ConnectionInterface;

class Channel
{
    /** @var string */
    protected $channelId;

    /** @var ConnectionInterface[] */
    protected $connections = [];

    public function __construct(string $channelId)
    {
        $this->channelId = $channelId;
    }

    public function hasConnections(): bool
    {
        return count($this->connections) > 0;
    }

    /**
     * @link https://pusher.com/docs/pusher_protocol#presence-channel-events
     *
     * @param ConnectionInterface $conn
     * @param $payload
     */
    public function subscribe(ConnectionInterface $conn, $payload)
    {
        $this->saveConnection($conn);

        // Send the success event
        $conn->send(json_encode([
            'event' => 'pusher_internal:subscription_succeeded',
            'channel' => $this->channelId
        ]));
    }

    public function unsubscribe(ConnectionInterface $connection)
    {
        unset($this->connections[$connection->socketId]);
    }

    protected function saveConnection(ConnectionInterface $conn)
    {
        $this->connections[$conn->socketId] = $conn;
    }

    public function broadcast($payload)
    {
        foreach ($this->connections as $connection) {
            $connection->send(json_encode($payload));
        }
    }

    public function broadcastToOthers($conn, $payload)
    {
        Collection::make($this->connections)->reject(function ($connection) use ($conn) {
            return $connection->socketId === $conn->socketId;
        })->each->send(json_encode($payload));
    }
}