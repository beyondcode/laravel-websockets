<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels;

use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\InvalidSignature;
use Illuminate\Support\Collection;
use Ratchet\ConnectionInterface;
use stdClass;

class Channel
{
    /** @var string */
    protected $channelName;

    /** @var \Ratchet\ConnectionInterface[] */
    protected $subscriptions = [];

    public function __construct(string $channelName)
    {
        $this->channelId = $channelName;
    }

    public function hasConnections(): bool
    {
        return count($this->subscriptions) > 0;
    }

    public function getSubscriptions(): array
    {
        return $this->subscriptions;
    }

    protected function verifySignature(ConnectionInterface $connection, stdClass $payload)
    {
        $signature = "{$connection->socketId}:{$this->channelId}";

        if (isset($payload->channel_data)) {
            $signature .= ":{$payload->channel_data}";
        }

        if (str_after($payload->auth, ':') !== hash_hmac('sha256', $signature, $connection->client->appSecret)) {
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
            'channel' => $this->channelId
        ]));
    }

    public function unsubscribe(ConnectionInterface $connection)
    {
        unset($this->subscriptions[$connection->socketId]);

        if (! $this->hasConnections()) {
            DashboardLogger::vacated($connection, $this->channelId);
        }
    }

    protected function saveConnection(ConnectionInterface $connection)
    {
        $hadConnectionsPreviously = $this->hasConnections();

        $this->subscriptions[$connection->socketId] = $connection;

        if (! $hadConnectionsPreviously) {
            DashboardLogger::occupied($connection, $this->channelId);
        }

        DashboardLogger::subscribed($connection, $this->channelId);
    }

    public function broadcast($payload)
    {
        foreach ($this->subscriptions as $connection) {
            $connection->send(json_encode($payload));
        }
    }

    public function broadcastToEveryoneExcept($payload, ?string $socketId = null)
    {
        if (is_null($socketId)) {
            return $this->broadcast($payload);
        }

        foreach ($this->subscriptions as $connection) {
            if ($connection->socketId !== $socketId) {
                $connection->send(json_encode($payload));
            }
        }
    }

    public function broadcastToOthers(ConnectionInterface $connection, $payload)
    {
        $this->broadcastToEveryoneExcept($payload, $connection->socketId);
    }

    public function toArray(): array
    {
        return [
            'occupied' => count($this->subscriptions) > 0,
            'subscription_count' => count($this->subscriptions)
        ];
    }
}