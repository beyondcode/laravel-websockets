<?php

namespace BeyondCode\LaravelWebSockets\Concerns;

use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\InvalidSignature;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use stdClass;

trait Channelable
{
    /**
     * The channel name.
     *
     * @var string
     */
    protected $channelName;

    /**
     * The replicator client.
     *
     * @var ReplicationInterface
     */
    protected $replicator;

    /**
     * The connections that got subscribed.
     *
     * @var array
     */
    protected $subscribedConnections = [];

    /**
     * Create a new instance.
     *
     * @param  string  $channelName
     * @return void
     */
    public function __construct(string $channelName)
    {
        $this->channelName = $channelName;
        $this->replicator = app(ReplicationInterface::class);
    }

    /**
     * Get the channel name.
     *
     * @return string
     */
    public function getChannelName(): string
    {
        return $this->channelName;
    }

    /**
     * Check if the channel has connections.
     *
     * @return bool
     */
    public function hasConnections(): bool
    {
        return count($this->subscribedConnections) > 0;
    }

    /**
     * Get all subscribed connections.
     *
     * @return array
     */
    public function getSubscribedConnections(): array
    {
        return $this->subscribedConnections;
    }

    /**
     * Check if the signature for the payload is valid.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \stdClass  $payload
     * @return void
     * @throws InvalidSignature
     */
    protected function verifySignature(ConnectionInterface $connection, stdClass $payload)
    {
        $signature = "{$connection->socketId}:{$this->channelName}";

        if (isset($payload->channel_data)) {
            $signature .= ":{$payload->channel_data}";
        }

        if (! hash_equals(
            hash_hmac('sha256', $signature, $connection->app->secret),
            Str::after($payload->auth, ':'))
        ) {
            throw new InvalidSignature();
        }
    }

    /**
     * Subscribe to the channel.
     *
     * @see    https://pusher.com/docs/pusher_protocol#presence-channel-events
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \stdClass  $payload
     * @return void
     */
    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        $this->saveConnection($connection);

        $connection->send(json_encode([
            'event' => 'pusher_internal:subscription_succeeded',
            'channel' => $this->channelName,
        ]));

        $this->replicator->subscribe($connection->app->id, $this->channelName);
    }

    /**
     * Unsubscribe connection from the channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function unsubscribe(ConnectionInterface $connection)
    {
        unset($this->subscribedConnections[$connection->socketId]);

        $this->replicator->unsubscribe($connection->app->id, $this->channelName);

        if (! $this->hasConnections()) {
            DashboardLogger::log($connection->app->id, DashboardLogger::TYPE_VACATED, [
                'socketId' => $connection->socketId,
                'channel' => $this->channelName,
            ]);
        }
    }

    /**
     * Store the connection to the subscribers list.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    protected function saveConnection(ConnectionInterface $connection)
    {
        $hadConnectionsPreviously = $this->hasConnections();

        $this->subscribedConnections[$connection->socketId] = $connection;

        if (! $hadConnectionsPreviously) {
            DashboardLogger::log($connection->app->id, DashboardLogger::TYPE_OCCUPIED, [
                'channel' => $this->channelName,
            ]);
        }

        DashboardLogger::log($connection->app->id, DashboardLogger::TYPE_SUBSCRIBED, [
            'socketId' => $connection->socketId,
            'channel' => $this->channelName,
        ]);
    }

    /**
     * Broadcast a payload to the subscribed connections.
     *
     * @param  \stdClass  $payload
     * @return void
     */
    public function broadcast($payload)
    {
        foreach ($this->subscribedConnections as $connection) {
            $connection->send(json_encode($payload));
        }
    }

    /**
     * Broadcast the payload, but exclude the current connection.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \stdClass  $payload
     * @return void
     */
    public function broadcastToOthers(ConnectionInterface $connection, stdClass $payload)
    {
        $this->broadcastToEveryoneExcept(
            $payload, $connection->socketId, $connection->app->id
        );
    }

    /**
     * Broadcast the payload, but exclude a specific socket id.
     *
     * @param  \stdClass  $payload
     * @param  string|null  $socketId
     * @param  mixed  $appId
     * @param  bool  $publish
     * @return void
     */
    public function broadcastToEveryoneExcept(stdClass $payload, ?string $socketId, $appId, bool $publish = true)
    {
        // Also broadcast via the other websocket server instances.
        // This is set false in the Redis client because we don't want to cause a loop
        // in this case. If this came from TriggerEventController, then we still want
        // to publish to get the message out to other server instances.
        if ($publish) {
            $this->replicator->publish($appId, $this->channelName, $payload);
        }

        // Performance optimization, if we don't have a socket ID,
        // then we avoid running the if condition in the foreach loop below
        // by calling broadcast() instead.
        if (is_null($socketId)) {
            $this->broadcast($payload);

            return;
        }

        foreach ($this->subscribedConnections as $connection) {
            if ($connection->socketId !== $socketId) {
                $connection->send(json_encode($payload));
            }
        }
    }

    /**
     * Convert the channel to array.
     *
     * @param  mixed  $appId
     * @return array
     */
    public function toArray($appId = null)
    {
        return [
            'occupied' => count($this->subscribedConnections) > 0,
            'subscription_count' => count($this->subscribedConnections),
        ];
    }
}
