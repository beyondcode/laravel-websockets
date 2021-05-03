<?php

namespace BeyondCode\LaravelWebSockets\ChannelManagers;

use Amp\Promise;
use BeyondCode\LaravelWebSockets\Channels\Channel;
use BeyondCode\LaravelWebSockets\Channels\PresenceChannel;
use BeyondCode\LaravelWebSockets\Channels\PrivateChannel;
use BeyondCode\LaravelWebSockets\Contracts\ChannelManager;
use BeyondCode\LaravelWebSockets\Contracts\Connection;
use BeyondCode\LaravelWebSockets\Helpers;
use Carbon\Carbon;
use Illuminate\Cache\ArrayLock;
use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use stdClass;

class LocalChannelManager implements ChannelManager
{
    /**
     * The list of stored channels.
     *
     * @var array|\BeyondCode\LaravelWebSockets\Channels\Channel[]
     */
    protected $channels = [];

    /**
     * The list of users that joined the presence channel.
     *
     * @var array|\Illuminate\Contracts\Auth\Authenticatable[]
     */
    protected $users = [];

    /**
     * The list of users by socket and their attached id.
     *
     * @var array
     */
    protected $userSockets = [];

    /**
     * Whether the current instance accepts new connections.
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
     */
    public function __construct()
    {
        $this->store = new ArrayStore;
        $this->serverId = Str::uuid()->toString();
    }

    /**
     * Find the channel by app & name.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @return \BeyondCode\LaravelWebSockets\Channels\Channel|null
     */
    public function find($appId, string $channel) : ?Channel
    {
        return $this->channels[$appId][$channel] ?? null;
    }

    /**
     * Find a channel by app & name or create one.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @return \BeyondCode\LaravelWebSockets\Channels\Channel
     */
    public function findOrCreate($appId, string $channel) : Channel
    {
        if (! $this->find($appId, $channel)) {
            $class = $this->getChannelClassName($channel);

            $this->channels[$appId][$channel] = new $class($channel);
        }

        return $this->channels[$appId][$channel];
    }

    /**
     * Get the local connections, regardless of the channel
     * they are connected to.
     *
     * @return \Amp\Promise
     */
    public function getLocalConnections(): Promise
    {
        $connections = collect($this->channels)
            ->map(static function (Channel $channelsWithConnections): Collection {
                return collect($channelsWithConnections)->values();
            })
            ->values()->collapse()
            ->map(static function (Channel $channel): Collection {
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
     *
     * @return \Amp\Promise
     */
    public function getLocalChannels($appId): Promise
    {
        return Helpers::createFulfilledPromise($this->channels[$appId] ?? []);
    }

    /**
     * Get all channels for a specific app
     * across multiple servers.
     *
     * @param  string|int  $appId
     *
     * @return \Amp\Promise
     */
    public function getGlobalChannels($appId): Promise
    {
        return $this->getLocalChannels($appId);
    }

    /**
     * Remove connection from all channels.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     *
     * @return \Amp\Promise
     */
    public function unsubscribeFromAllChannels(Connection $connection): Promise
    {
        if (!$appId = $connection->getAppId()) {
            return Helpers::createFulfilledPromise(false);
        }

        $this->getLocalChannels($appId)
            ->onResolve(function (array $channels) use ($connection): void {
                collect($channels)
                    ->each(static function (Channel $channel) use ($connection): void {
                        $channel->unsubscribe($connection);
                    });

                collect($channels)
                    ->reject(static function (Channel $channel): bool {
                        return $channel->hasConnections();
                    })
                    ->each(function (Channel $channel, string $channelName) use ($connection): void {
                        unset($this->channels[$connection->getAppId()][$channelName]);
                    });
            });

        $this->getLocalChannels($connection->getAppId())
            ->onResolve(function ($channels) use ($connection): void {
                if (count($channels) === 0) {
                    unset($this->channels[$connection->getAppId()]);
                }
            });

        return Helpers::createFulfilledPromise(true);
    }

    /**
     * Subscribe the connection to a specific channel.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  string  $channelName
     * @param  object|array  $payload
     *
     * @return \Amp\Promise
     */
    public function subscribeToChannel(Connection $connection, string $channelName, $payload): Promise
    {
        return Helpers::createFulfilledPromise(
            $this->findOrCreate($connection->getAppId(), $channelName)->subscribe($connection, $payload)
        );
    }

    /**
     * Unsubscribe the connection from the channel.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  string  $channelName
     * @param  object|array  $payload
     *
     * @return \Amp\Promise
     */
    public function unsubscribeFromChannel(Connection $connection, string $channelName, $payload): Promise
    {
        return Helpers::createFulfilledPromise(
            $this->findOrCreate($connection->getAppId(), $channelName)->unsubscribe($connection, $payload)
        );
    }

    /**
     * Subscribe the connection to a specific channel, returning
     * a promise containing the amount of connections.
     *
     * @param  string|int  $appId
     *
     * @return \Amp\Promise
     */
    public function subscribeToApp($appId): Promise
    {
        return Helpers::createFulfilledPromise(0);
    }

    /**
     * Unsubscribe the connection from the channel, returning
     * a promise containing the amount of connections after decrement.
     *
     * @param  string|int  $appId
     * @return \Amp\Promise
     */
    public function unsubscribeFromApp($appId): Promise
    {
        return Helpers::createFulfilledPromise(0);
    }

    /**
     * Get the connections count on the app
     * for the current server instance.
     *
     * @param  string|int  $appId
     * @param  string|null  $channelName
     * @return \Amp\Promise
     */
    public function getLocalConnectionsCount($appId, string $channelName = null): Promise
    {
        $localChannels = $this->getLocalChannels($appId);

        $localChannels->onResolve(function (array $channels) use ($channelName) {
            return collect($channels)
                ->when($channelName, static function (Collection $collection) use ($channelName): Collection {
                    return $collection->filter(static function (Channel $channel) use ($channelName): bool {
                        return $channel->getName() === $channelName;
                    });
                })
                ->flatMap(static function (Channel $channel): Collection {
                    return collect($channel->getConnections())->pluck('socketId');
                })
                ->unique()->count();
        });

        return $localChannels;
    }

    /**
     * Get the connections count
     * across multiple servers.
     *
     * @param  string|int  $appId
     * @param  string|null  $channelName
     * @return \Amp\Promise
     */
    public function getGlobalConnectionsCount($appId, string $channelName = null): Promise
    {
        return $this->getLocalConnectionsCount($appId, $channelName);
    }

    /**
     * Broadcast the message across multiple servers.
     *
     * @param  string|int  $appId
     * @param  string|null  $socketId
     * @param  string  $channel
     * @param  object|array  $payload
     * @param  string|null  $serverId
     * @return \Amp\Promise
     */
    public function broadcastAcrossServers($appId, ?string $socketId, string $channel, $payload, string $serverId = null): Promise
    {
        return Helpers::createFulfilledPromise(true);
    }

    /**
     * Handle the user when it joined a presence channel.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  object  $user
     * @param  string  $channel
     * @param  object|array  $payload
     *
     * @return \Amp\Promise
     */
    public function userJoinedPresenceChannel(Connection $connection, object $user, string $channel, $payload): Promise
    {
        $this->users["{$connection->getAppId()}:{$channel}"][$connection->getId()] = json_encode($user);
        $this->userSockets["{$connection->getAppId()}:{$channel}:{$user->user_id}"][] = $connection->getId();

        return Helpers::createFulfilledPromise(true);
    }

    /**
     * Handle the user when it left a presence channel.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  object  $user
     * @param  string  $channel
     *
     * @return \Amp\Promise
     */
    public function userLeftPresenceChannel(Connection $connection, object $user, string $channel): Promise
    {
        unset($this->users["{$connection->getAppId()}:{$channel}"][$connection->getId()]);

        $deletableSocketKey = array_search(
            $connection->getId(),
            $this->userSockets["{$connection->getAppId()}:{$channel}:{$user->user_id}"] ?? [],
            true
        );

        if ($deletableSocketKey !== false) {
            unset($this->userSockets["{$connection->getAppId()}:{$channel}:{$user->user_id}"][$deletableSocketKey]);

            if (count($this->userSockets["{$connection->getAppId()}:{$channel}:{$user->user_id}"]) === 0) {
                unset($this->userSockets["{$connection->getAppId()}:{$channel}:{$user->user_id}"]);
            }
        }

        return Helpers::createFulfilledPromise(true);
    }

    /**
     * Get the presence channel members.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     *
     * @return \Amp\Promise
     */
    public function getChannelMembers($appId, string $channel): Promise
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
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  string  $channel
     *
     * @return \Amp\Promise
     */
    public function getChannelMember(Connection $connection, string $channel): Promise
    {
        $member = $this->users["{$connection->app->id}:{$channel}"][$connection->getId()] ?? null;

        return Helpers::createFulfilledPromise($member);
    }

    /**
     * Get the presence channels total members count.
     *
     * @param  string|int  $appId
     * @param  array  $channelNames
     *
     * @return \Amp\Promise
     */
    public function getChannelsMembersCount($appId, array $channelNames): Promise
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
     *
     * @return \Amp\Promise
     */
    public function getMemberSockets($userId, $appId, string $channelName): Promise
    {
        return Helpers::createFulfilledPromise(
            $this->userSockets["{$appId}:{$channelName}:{$userId}"] ?? []
        );
    }

    /**
     * Keep tracking the connections availability when they pong.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     *
     * @return \Amp\Promise
     */
    public function connectionPonged(Connection $connection): Promise
    {
        $connection->getClient()->getInfo()->lastHeartbeatAt = Carbon::now()->timestamp;

        return $this->updateConnectionInChannels($connection);
    }

    /**
     * Remove the obsolete connections that didn't ponged in a while.
     *
     * @return \Amp\Promise
     */
    public function removeObsoleteConnections(): Promise
    {
        if (! $this->lock()->acquire()) {
            return Helpers::createFulfilledPromise(false);
        }

        $this->getLocalConnections()->onResolve(function ($error, $connections): void {
            if ($error) {
                throw $error;
            }

            /** @var \BeyondCode\LaravelWebSockets\Contracts\Connection $connection */
            foreach ($connections as $connection) {
                $differenceInSeconds = Carbon::now()->timestamp - $connection->getClient()->getInfo()->lastHeartbeatAt;

                if ($differenceInSeconds > 120) {
                    $this->unsubscribeFromAllChannels($connection);
                }
            }
        });

        return Helpers::createFulfilledPromise($this->lock()->forceRelease());
    }

    /**
     * Update the connection in all channels.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     *
     * @return \Amp\Promise [bool]
     */
    public function updateConnectionInChannels(Connection $connection): Promise
    {
        $localChannels = $this->getLocalChannels($connection->getAppId());

        $localChannels->onResolve(static function (array $channels) use ($connection): bool {
            foreach ($channels as $channel) {
                if ($channel->hasConnection($connection)) {
                    $channel->saveConnection($connection);
                }
            }

            return true;
        });

        return $localChannels;
    }

    /**
     * Mark the current instance as unable to accept new connections.
     *
     * @return $this
     */
    public function declineNewConnections(): LocalChannelManager
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
     * @return \Illuminate\Cache\ArrayLock
     */
    protected function lock(): Lock
    {
        return new ArrayLock($this->store, static::$lockName, 0);
    }
}
