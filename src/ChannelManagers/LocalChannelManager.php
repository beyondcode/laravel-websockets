<?php

namespace BeyondCode\LaravelWebSockets\ChannelManagers;

use BeyondCode\LaravelWebSockets\Channels\Channel;
use BeyondCode\LaravelWebSockets\Channels\PresenceChannel;
use BeyondCode\LaravelWebSockets\Channels\PrivateChannel;
use BeyondCode\LaravelWebSockets\Contracts\ChannelManager;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use React\Promise\FulfilledPromise;
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
     * Wether the current instance accepts new connections.
     *
     * @var bool
     */
    protected $acceptsNewConnections = true;

    /**
     * Create a new channel manager instance.
     *
     * @param  LoopInterface  $loop
     * @param  string|null  $factoryClass
     * @return void
     */
    public function __construct(LoopInterface $loop, $factoryClass = null)
    {
        //
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

        return new FulfilledPromise($connections);
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
        return new FulfilledPromise(
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
     * @return void
     */
    public function unsubscribeFromAllChannels(ConnectionInterface $connection)
    {
        if (! isset($connection->app)) {
            return;
        }

        $this->getLocalChannels($connection->app->id)
            ->then(function ($channels) use ($connection) {
                collect($channels)->each->unsubscribe($connection);

                collect($channels)
                    ->reject->hasConnections()
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
    }

    /**
     * Subscribe the connection to a specific channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  string  $channelName
     * @param  stdClass  $payload
     * @return void
     */
    public function subscribeToChannel(ConnectionInterface $connection, string $channelName, stdClass $payload)
    {
        $channel = $this->findOrCreate($connection->app->id, $channelName);

        $channel->subscribe($connection, $payload);
    }

    /**
     * Unsubscribe the connection from the channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  string  $channelName
     * @param  stdClass  $payload
     * @return void
     */
    public function unsubscribeFromChannel(ConnectionInterface $connection, string $channelName, stdClass $payload)
    {
        $channel = $this->findOrCreate($connection->app->id, $channelName);

        $channel->unsubscribe($connection, $payload);
    }

    /**
     * Subscribe the connection to a specific channel.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function subscribeToApp($appId)
    {
        //
    }

    /**
     * Unsubscribe the connection from the channel.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function unsubscribeFromApp($appId)
    {
        //
    }

    /**
     * Get the connections count on the app
     * for the current server instance.
     *
     * @param  string|int  $appId
     * @param  string|null  $channelName
     * @return \React\Promise\PromiseInterface
     */
    public function getLocalConnectionsCount($appId, string $channelName = null): PromiseInterface
    {
        return $this->getLocalChannels($appId)
            ->then(function ($channels) use ($channelName) {
                return collect($channels)
                    ->when(! is_null($channelName), function ($collection) use ($channelName) {
                        return $collection->filter(function (Channel $channel) use ($channelName) {
                            return $channel->getName() === $channelName;
                        });
                    })
                    ->flatMap(function (Channel $channel) {
                        return collect($channel->getConnections())->pluck('socketId');
                    })
                    ->unique()
                    ->count();
            });
    }

    /**
     * Get the connections count
     * across multiple servers.
     *
     * @param  string|int  $appId
     * @param  string|null  $channelName
     * @return \React\Promise\PromiseInterface
     */
    public function getGlobalConnectionsCount($appId, string $channelName = null): PromiseInterface
    {
        return $this->getLocalConnectionsCount($appId, $channelName);
    }

    /**
     * Broadcast the message across multiple servers.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @param  stdClass  $payload
     * @return bool
     */
    public function broadcastAcrossServers($appId, string $channel, stdClass $payload)
    {
        return true;
    }

    /**
     * Handle the user when it joined a presence channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  stdClass  $user
     * @param  string  $channel
     * @param  stdClass  $payload
     * @return void
     */
    public function userJoinedPresenceChannel(ConnectionInterface $connection, stdClass $user, string $channel, stdClass $payload)
    {
        $this->users["{$connection->app->id}:{$channel}"][$connection->socketId] = json_encode($user);
    }

    /**
     * Handle the user when it left a presence channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  stdClass  $user
     * @param  string  $channel
     * @param  stdClass  $payload
     * @return void
     */
    public function userLeftPresenceChannel(ConnectionInterface $connection, stdClass $user, string $channel)
    {
        unset($this->users["{$connection->app->id}:{$channel}"][$connection->socketId]);
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
        })->toArray();

        return new FulfilledPromise($members);
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

        return new FulfilledPromise($member);
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

        return new FulfilledPromise($results);
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
}
