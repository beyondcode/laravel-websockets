<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels;

use stdClass;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\InvalidSignature;

class Channel
{
    /** @var string */
    protected $channelName;

    /** @var \Ratchet\ConnectionInterface[] */
    protected $subscribedConnections = [];

    public function __construct(string $channelName)
    {
        $this->channelName = $channelName;
    }

    public function hasConnections(): bool
    {
        return count($this->subscribedConnections) > 0;
    }

    public function getSubscribedConnections(): array
    {
        return $this->subscribedConnections;
    }

    protected function verifySignature(ConnectionInterface $connection, stdClass $payload)
    {
        $signature = "{$connection->socketId}:{$this->channelName}";

        if (isset($payload->channel_data)) {
            $signature .= ":{$payload->channel_data}";
        }

        if (Str::after($payload->auth, ':') !== hash_hmac('sha256', $signature, $connection->app->secret)) {
            throw new InvalidSignature();
        }
    }

    /*
     * @link https://pusher.com/docs/pusher_protocol#presence-channel-events
     */
    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $this->saveConnection($connection);

        $connection->send(json_encode([
            'event' => 'pusher_internal:subscription_succeeded',
            'channel' => $this->channelName,
        ]));
    }

    public function unsubscribe(ConnectionInterface $connection)
    {
        unset($this->subscribedConnections[$connection->socketId]);

        if (! $this->hasConnections()) {
            DashboardLogger::vacated($connection, $this->channelName);
        }
    }

    protected function saveConnection(ConnectionInterface $connection)
    {
        $hadConnectionsPreviously = $this->hasConnections();

        $this->subscribedConnections[$connection->socketId] = $connection;

        if (! $hadConnectionsPreviously) {
            DashboardLogger::occupied($connection, $this->channelName);
        }

        DashboardLogger::subscribed($connection, $this->channelName);
    }

    public function broadcast($payload)
    {
        foreach ($this->subscribedConnections as $connection) {
            $connection->send(json_encode($payload));
        }
    }

    public function broadcastToOthers(ConnectionInterface $connection, $payload)
    {
        $this->broadcastToEveryoneExcept($payload, $connection->socketId);
    }

    public function broadcastToEveryoneExcept($payload, ?string $socketId = null)
    {
        if (is_null($socketId)) {
            return $this->broadcast($payload);
        }

        foreach ($this->subscribedConnections as $connection) {
            if ($connection->socketId !== $socketId) {
                $connection->send(json_encode($payload));
            }
        }
    }

    public function toArray(): array
    {
        return [
            'occupied' => count($this->subscribedConnections) > 0,
            'subscription_count' => count($this->subscribedConnections),
        ];
    }
}
