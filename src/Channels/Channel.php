<?php

namespace BeyondCode\LaravelWebSockets\Channels;

use BeyondCode\LaravelWebSockets\Contracts\ChannelManager;
use BeyondCode\LaravelWebSockets\Contracts\Connection;
use BeyondCode\LaravelWebSockets\DashboardLogger;
use BeyondCode\LaravelWebSockets\Events\SubscribedToChannel;
use BeyondCode\LaravelWebSockets\Events\UnsubscribedFromChannel;
use BeyondCode\LaravelWebSockets\Server\Exceptions\InvalidSignature;
use Illuminate\Support\Str;

class Channel
{
    /**
     * The channel name.
     *
     * @var string
     */
    protected $name;

    /**
     * The connections that got subscribed to this channel.
     *
     * @var array|\BeyondCode\LaravelWebSockets\Contracts\Connection[]
     */
    protected $connections = [];

    /**
     * Channel manager
     *
     * @var \BeyondCode\LaravelWebSockets\Contracts\ChannelManager
     */
    private $channelManager;

    /**
     * Create a new instance.
     *
     * @param  string  $name
     * @return void
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->channelManager = app(ChannelManager::class);
    }

    /**
     * Get channel name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the list of subscribed connections.
     *
     * @return array|\BeyondCode\LaravelWebSockets\Contracts\Connection[]
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * Check if the channel has connections.
     *
     * @return bool
     */
    public function hasConnections(): bool
    {
        return count($this->getConnections()) > 0;
    }

    /**
     * Add a new connection to the channel.
     *
     * @see    https://pusher.com/docs/pusher_protocol#presence-channel-events
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  object|array  $payload
     *
     * @return bool
     */
    public function subscribe(Connection $connection, $payload): bool
    {
        $this->saveConnection($connection);

        if ($connection->getClient()->isConnected()) {
            $connection->getClient()->send(json_encode([
                'event' => 'pusher_internal:subscription_succeeded',
                'channel' => $this->getName(),
            ]));
        }

        DashboardLogger::log($connection->getAppId(), DashboardLogger::TYPE_SUBSCRIBED, [
            'socketId' => $connection->getId(),
            'channel' => $this->getName(),
        ]);

        SubscribedToChannel::dispatch($connection->getAppId(), $connection->getId(), $this->getName());

        return true;
    }

    /**
     * Unsubscribe connection from the channel.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  object|array|null  $payload
     *
     * @return bool
     */
    public function unsubscribe(Connection $connection, $payload = null): bool
    {
        if (! $this->hasConnection($connection)) {
            return false;
        }

        unset($this->connections[$connection->getId()]);

        if ($payload && $connection->getClient()->isConnected()) {
            $connection->getClient()->send(json_encode($payload));
        }

        UnsubscribedFromChannel::dispatch(
            $connection->getAppId(),
            $connection->getId(),
            $this->getName()
        );

        return true;
    }

    /**
     * Check if the given connection exists.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     *
     * @return bool
     */
    public function hasConnection(Connection $connection): bool
    {
        return isset($this->connections[$connection->getClient()->getId()]);
    }

    /**
     * Store the connection to the subscribers list.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     *
     * @return void
     */
    public function saveConnection(Connection $connection): void
    {
        $this->connections[$connection->getId()] = $connection;
    }

    /**
     * Broadcast a payload to the subscribed connections.
     *
     * @param  string|int  $appId
     * @param  object|array  $payload
     * @param  bool  $replicate
     *
     * @return bool
     */
    public function broadcast($appId, $payload, bool $replicate = true): bool
    {
        collect($this->getConnections())
            ->each(function (Connection $connection) use ($payload) {
                if ($connection->getClient()->isConnected()) {
                    $connection->getClient()->send(json_encode($payload));
                }
            });

        if ($replicate) {
            $this->channelManager->broadcastAcrossServers($appId, null, $this->getName(), $payload);
        }

        return true;
    }

    /**
     * Broadcast a payload to the locally-subscribed connections.
     *
     * @param  string|int  $appId
     * @param  object|array  $payload
     *
     * @return bool
     */
    public function broadcastLocally($appId, $payload): bool
    {
        return $this->broadcast($appId, $payload, false);
    }

    /**
     * Broadcast the payload, but exclude a specific socket id.
     *
     * @param  object|array  $payload
     * @param  int|null  $socketId
     * @param  string|int  $appId
     * @param  bool  $replicate
     *
     * @return bool
     */
    public function broadcastToEveryoneExcept($payload, ?int $socketId, $appId, bool $replicate = true): bool
    {
        if ($replicate) {
            $this->channelManager->broadcastAcrossServers($appId, $socketId, $this->getName(), $payload);
        }

        if (is_null($socketId)) {
            return $this->broadcast($appId, $payload, $replicate);
        }

        collect($this->getConnections())->each(function (Connection $connection) use ($socketId, $payload) {
            if (($connection->getId() !== $socketId) && $connection->getClient()->isConnected()) {
                $connection->getClient()->send(json_encode($payload));
            }
        });

        return true;
    }

    /**
     * Broadcast the payload, but exclude a specific socket id.
     *
     * @param  object|array  $payload
     * @param  string|null  $socketId
     * @param  string|int  $appId
     * @return bool
     */
    public function broadcastLocallyToEveryoneExcept($payload, ?string $socketId, $appId): bool
    {
        return $this->broadcastToEveryoneExcept(
            $payload, $socketId, $appId, false
        );
    }

    /**
     * Check if the signature for the payload is valid.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  object|array  $payload
     *
     * @return void
     * @throws \BeyondCode\LaravelWebSockets\Server\Exceptions\InvalidSignature
     */
    protected function verifySignature(Connection $connection, $payload): void
    {
        $signature = "{$connection->getId()}:{$this->getName()}";

        if (isset($payload->channel_data)) {
            $signature .= ":{$payload->channel_data}";
        }

        if (! hash_equals(
            hash_hmac('sha256', $signature, $connection->app->secret),
            Str::after($payload->auth, ':'))
        ) {
            throw new InvalidSignature;
        }
    }
}
