<?php

namespace BeyondCode\LaravelWebSockets\ChannelManagers;

use BeyondCode\LaravelWebSockets\Channels\Channel;
use BeyondCode\LaravelWebSockets\Channels\PresenceChannel;
use BeyondCode\LaravelWebSockets\Channels\PrivateChannel;
use BeyondCode\LaravelWebSockets\Contracts\ChannelManager;
use BeyondCode\LaravelWebSockets\Helpers;
use Carbon\Carbon;
use Illuminate\Cache\ArrayLock;
use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use stdClass;

class LocalChannelManager implements ChannelManager
{
    /**
     * The list of stored channels.
     *
     * @var array
     */
    protected $channels = [];

    /**
     * The list of users that joined the presence channel.
     *
     * @var array
     */
    protected $users = [];

    /**
     * The list of users by socket and their attached id.
     *
     * @var array
     */
    protected $userSockets = [];

    /**
     * Wether the current instance accepts new connections.
     *
     * @var bool
     */
    protected $acceptsNewConnections = true;

    /**
     * The ArrayStore instance of locks.
     *
     * @var \Illuminate\Cache\ArrayStore
     */
    protected $store;

    /**
     * The unique server identifier.
     *
     * @var string
     */
    protected $serverId;

    /**
     * The lock name to use on Array to avoid multiple
     * actions that might lead to multiple processings.
     *
     * @var string
     */
    protected static $lockName = 'laravel-websockets:channel-manager:lock';

    /**
     * Create a new channel manager instance.
     *
     * @param  LoopInterface  $loop
     * @param  string|null  $factoryClass
     * @return void
     */
    public function __construct(LoopInterface $loop, $factoryClass = null)
    {
        $this->store = new ArrayStore;
        $this->serverId = Str::uuid()->toString();
    }

    /**
     * Find the channel by app & name.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @return null|BeyondCode\LaravelWebSockets\Channels\Channel
     */
    public function find($appId, string $channel)
    {
        return $this->channels[$appId][$channel] ?? null;
    }

    /**
     * Find a channel by app & name or create one.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @return BeyondCode\LaravelWebSockets\Channels\Channel
     */
    public function findOrCreate($appId, string $channel)
    {
        if (! $channelInstance = $this->find($appId, $channel)) {
            $class = $this->getChannelClassName($channel);

            $this->channels[$appId][$channel] = new $class($channel);
        }

        return $this->channels[$appId][$channel];
    }

    /**
     * Get the local connections, regardless of the channel
     * they are connected to.
     *
     * @return \React\Promise\PromiseInterface
     */
    public function getLocalConnections(): PromiseInterface
    {
        $connections = collect($this->channels)
            ->map(function ($channelsWithConnections, $appId) {
                return collect($channelsWithConnections)->values();
            })
            ->values()->collapse()
            ->map(function ($channel) {
                return collect($channel->getConnections());
            })
            ->values()->collapse()
            ->toArray();

        return Helpers::createFulfilledPromise($connections);
    }

    /**
     * Get all channels for a specific app
     * for the current instance.
     *
     * @param  string|int  $appId
     * @return \React\Promise\PromiseInterface[array]
     */
    public function getLocalChannels($appId): PromiseInterface
    {
        return Helpers::createFulfilledPromise(
            $this->channels[$appId] ?? []
        );
    }

    /**
     * Get all channels for a specific app
     * across multiple servers.
     *
     * @param  string|int  $appId
     * @return \React\Promise\PromiseInterface[array]
     */
    public function getGlobalChannels($appId): PromiseInterface
    {
        return $this->getLocalChannels($appId);
    }

    /**
     * Remove connection from all channels.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return PromiseInterface[bool]
     */
    public function unsubscribeFromAllChannels(ConnectionInterface $connection): PromiseInterface
    {
        if (! isset($connection->app)) {
            return Helpers::createFulfilledPromise(false);
        }

        $this->getLocalChannels($connection->app->id)
            ->then(function ($channels) use ($connection) {
                collect($channels)
                    ->each(function (Channel $channel) use ($connection) {
                        $channel->unsubscribe($connection);
                    });

                collect($channels)
                    ->reject(function ($channel) {
                        return $channel->hasConnections();
                    })
                    ->each(function (Channel $channel, string $channelName) use ($connection) {
                        unset($this->channels[$connection->app->id][$channelName]);
                    });
            });

        $this->getLocalChannels($connection->app->id)
            ->then(function ($channels) use ($connection) {
                if (count($channels) === 0) {
                    unset($this->channels[$connection->app->id]);
                }
            });

        return Helpers::createFulfilledPromise(true);
    }

    /**
     * Subscribe the connection to a specific channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  string  $channelName
     * @param  stdClass  $payload
     * @return PromiseInterface[bool]
     */
    public function subscribeToChannel(ConnectionInterface $connection, string $channelName, stdClass $payload): PromiseInterface
    {
        $channel = $this->findOrCreate($connection->app->id, $channelName);

        return Helpers::createFulfilledPromise(
            $channel->subscribe($connection, $payload)
        );
    }

    /**
     * Unsubscribe the connection from the channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  string  $channelName
     * @param  stdClass  $payload
     * @return PromiseInterface[bool]
     */
    public function unsubscribeFromChannel(ConnectionInterface $connection, string $channelName, stdClass $payload): PromiseInterface
    {
        $channel = $this->findOrCreate($connection->app->id, $channelName);

        return Helpers::createFulfilledPromise(
            $channel->unsubscribe($connection, $payload)
        );
    }

    /**
     * Subscribe the connection to a specific channel, returning
     * a promise containing the amount of connections.
     *
     * @param  string|int  $appId
     * @return PromiseInterface[int]
     */
    public function subscribeToApp($appId): PromiseInterface
    {
        return Helpers::createFulfilledPromise(0);
    }

    /**
     * Unsubscribe the connection from the channel, returning
     * a promise containing the amount of connections after decrement.
     *
     * @param  string|int  $appId
     * @return PromiseInterface[int]
     */
    public function unsubscribeFromApp($appId): PromiseInterface
    {
        return Helpers::createFulfilledPromise(0);
    }

    /**
     * Get the connections count on the app
     * for the current server instance.
     *
     * @param  string|int  $appId
     * @param  string|null  $channelName
     * @return PromiseInterface[int]
     */
    public function getLocalConnectionsCount($appId, string $channelName = null): PromiseInterface
    {
        return $this->getLocalChannels($appId)
            ->then(function ($channels) use ($channelName) {
                return collect($channels)->when(! is_null($channelName), function ($collection) use ($channelName) {
                    return $collection->filter(function (Channel $channel) use ($channelName) {
                        return $channel->getName() === $channelName;
                    });
                })
                    ->flatMap(function (Channel $channel) {
                        return collect($channel->getConnections())->pluck('socketId');
                    })
                    ->unique()->count();
            });
    }

    /**
     * Get the connections count
     * across multiple servers.
     *
     * @param  string|int  $appId
     * @param  string|null  $channelName
     * @return PromiseInterface[int]
     */
    public function getGlobalConnectionsCount($appId, string $channelName = null): PromiseInterface
    {
        return $this->getLocalConnectionsCount($appId, $channelName);
    }

    /**
     * Broadcast the message across multiple servers.
     *
     * @param  string|int  $appId
     * @param  string|null  $socketId
     * @param  string  $channel
     * @param  stdClass  $payload
     * @param  string|null  $serverId
     * @return PromiseInterface[bool]
     */
    public function broadcastAcrossServers($appId, ?string $socketId, string $channel, stdClass $payload, string $serverId = null): PromiseInterface
    {
        return Helpers::createFulfilledPromise(true);
    }

    /**
     * Handle the user when it joined a presence channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  stdClass  $user
     * @param  string  $channel
     * @param  stdClass  $payload
     * @return PromiseInterface[bool]
     */
    public function userJoinedPresenceChannel(ConnectionInterface $connection, stdClass $user, string $channel, stdClass $payload): PromiseInterface
    {
        $this->users["{$connection->app->id}:{$channel}"][$connection->socketId] = json_encode($user);
        $this->userSockets["{$connection->app->id}:{$channel}:{$user->user_id}"][] = $connection->socketId;

        return Helpers::createFulfilledPromise(true);
    }

    /**
     * Handle the user when it left a presence channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  stdClass  $user
     * @param  string  $channel
     * @param  stdClass  $payload
     * @return PromiseInterface[bool]
     */
    public function userLeftPresenceChannel(ConnectionInterface $connection, stdClass $user, string $channel): PromiseInterface
    {
        unset($this->users["{$connection->app->id}:{$channel}"][$connection->socketId]);

        $deletableSocketKey = array_search(
            $connection->socketId,
            $this->userSockets["{$connection->app->id}:{$channel}:{$user->user_id}"]
        );

        if ($deletableSocketKey !== false) {
            unset($this->userSockets["{$connection->app->id}:{$channel}:{$user->user_id}"][$deletableSocketKey]);

            if (count($this->userSockets["{$connection->app->id}:{$channel}:{$user->user_id}"]) === 0) {
                unset($this->userSockets["{$connection->app->id}:{$channel}:{$user->user_id}"]);
            }
        }

        return Helpers::createFulfilledPromise(true);
    }

    /**
     * Get the presence channel members.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @return \React\Promise\PromiseInterface
     */
    public function getChannelMembers($appId, string $channel): PromiseInterface
    {
        $members = $this->users["{$appId}:{$channel}"] ?? [];

        $members = collect($members)->map(function ($user) {
            return json_decode($user);
        })->unique('user_id')->toArray();

        return Helpers::createFulfilledPromise($members);
    }

    /**
     * Get a member from a presence channel based on connection.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  string  $channel
     * @return \React\Promise\PromiseInterface
     */
    public function getChannelMember(ConnectionInterface $connection, string $channel): PromiseInterface
    {
        $member = $this->users["{$connection->app->id}:{$channel}"][$connection->socketId] ?? null;

        return Helpers::createFulfilledPromise($member);
    }

    /**
     * Get the presence channels total members count.
     *
     * @param  string|int  $appId
     * @param  array  $channelNames
     * @return \React\Promise\PromiseInterface
     */
    public function getChannelsMembersCount($appId, array $channelNames): PromiseInterface
    {
        $results = collect($channelNames)
            ->reduce(function ($results, $channel) use ($appId) {
                $results[$channel] = isset($this->users["{$appId}:{$channel}"])
                    ? count($this->users["{$appId}:{$channel}"])
                    : 0;

                return $results;
            }, []);

        return Helpers::createFulfilledPromise($results);
    }

    /**
     * Get the socket IDs for a presence channel member.
     *
     * @param  string|int  $userId
     * @param  string|int  $appId
     * @param  string  $channelName
     * @return \React\Promise\PromiseInterface
     */
    public function getMemberSockets($userId, $appId, $channelName): PromiseInterface
    {
        return Helpers::createFulfilledPromise(
            $this->userSockets["{$appId}:{$channelName}:{$userId}"] ?? []
        );
    }

    /**
     * Keep tracking the connections availability when they pong.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return PromiseInterface[bool]
     */
    public function connectionPonged(ConnectionInterface $connection): PromiseInterface
    {
        return $this->pongConnectionInChannels($connection);
    }

    /**
     * Remove the obsolete connections that didn't ponged in a while.
     *
     * @return PromiseInterface[bool]
     */
    public function removeObsoleteConnections(): PromiseInterface
    {
        $lock = $this->lock();
        try {
            if (! $lock->acquire()) {
                return Helpers::createFulfilledPromise(false);
            }

            $this->getLocalConnections()->then(function ($connections) {
                foreach ($connections as $connection) {
                    $differenceInSeconds = $connection->lastPongedAt->diffInSeconds(Carbon::now());

                    if ($differenceInSeconds > 120) {
                        $this->unsubscribeFromAllChannels($connection);
                    }
                }
            });

            return Helpers::createFulfilledPromise(true);
        } finally {
            optional($lock)->forceRelease();
        }
    }

    /**
     * Pong connection in channels.
     *
     * @param  ConnectionInterface  $connection
     * @return PromiseInterface[bool]
     */
    public function pongConnectionInChannels(ConnectionInterface $connection): PromiseInterface
    {
        return $this->getLocalChannels($connection->app->id)
            ->then(function ($channels) use ($connection) {
                foreach ($channels as $channel) {
                    if ($conn = $channel->getConnection($connection->socketId)) {
                        $conn->lastPongedAt = Carbon::now();
                        $channel->saveConnection($conn);
                    }
                }

                return true;
            });
    }

    /**
     * Update the connection in all channels.
     *
     * @param  ConnectionInterface  $connection
     * @return PromiseInterface[bool]
     */
    public function updateConnectionInChannels($connection): PromiseInterface
    {
        return $this->getLocalChannels($connection->app->id)
            ->then(function ($channels) use ($connection) {
                foreach ($channels as $channel) {
                    if ($channel->hasConnection($connection)) {
                        $channel->saveConnection($connection);
                    }
                }

                return true;
            });
    }

    /**
     * Mark the current instance as unable to accept new connections.
     *
     * @return $this
     */
    public function declineNewConnections()
    {
        $this->acceptsNewConnections = false;

        return $this;
    }

    /**
     * Check if the current server instance
     * accepts new connections.
     *
     * @return bool
     */
    public function acceptsNewConnections(): bool
    {
        return $this->acceptsNewConnections;
    }

    /**
     * Get the channel class by the channel name.
     *
     * @param  string  $channelName
     * @return string
     */
    protected function getChannelClassName(string $channelName): string
    {
        if (Str::startsWith($channelName, 'private-')) {
            return PrivateChannel::class;
        }

        if (Str::startsWith($channelName, 'presence-')) {
            return PresenceChannel::class;
        }

        return Channel::class;
    }

    /**
     * Get the unique identifier for the server.
     *
     * @return string
     */
    public function getServerId(): string
    {
        return $this->serverId;
    }

    /**
     * Get a new ArrayLock instance to avoid race conditions.
     *
     * @return \Illuminate\Cache\CacheLock
     */
    protected function lock()
    {
        return new ArrayLock($this->store, static::$lockName, 0);
    }
}
