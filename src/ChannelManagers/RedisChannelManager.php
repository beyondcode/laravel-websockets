<?php

namespace BeyondCode\LaravelWebSockets\ChannelManagers;

use BeyondCode\LaravelWebSockets\Channels\Channel;
use BeyondCode\LaravelWebSockets\Helpers;
use BeyondCode\LaravelWebSockets\Server\MockableConnection;
use Carbon\Carbon;
use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use stdClass;

class RedisChannelManager extends LocalChannelManager
{
    /**
     * The running loop.
     *
     * @var LoopInterface
     */
    protected $loop;

    /**
     * The unique server identifier.
     *
     * @var string
     */
    protected $serverId;

    /**
     * The pub client.
     *
     * @var Client
     */
    protected $publishClient;

    /**
     * The sub client.
     *
     * @var Client
     */
    protected $subscribeClient;

    /**
     * The Redis manager instance.
     *
     * @var \Illuminate\Redis\RedisManager
     */
    protected $redis;

    /**
     * The lock name to use on Redis to avoid multiple
     * actions that might lead to multiple processings.
     *
     * @var string
     */
    protected static $redisLockName = 'laravel-websockets:channel-manager:lock';

    /**
     * Create a new channel manager instance.
     *
     * @param  LoopInterface  $loop
     * @param  string|null  $factoryClass
     * @return void
     */
    public function __construct(LoopInterface $loop, $factoryClass = null)
    {
        $this->loop = $loop;

        $this->redis = Redis::connection(
            config('websockets.replication.modes.redis.connection', 'default')
        );

        $connectionUri = $this->getConnectionUri();

        $factoryClass = $factoryClass ?: Factory::class;
        $factory = new $factoryClass($this->loop);

        $this->publishClient = $factory->createLazyClient($connectionUri);
        $this->subscribeClient = $factory->createLazyClient($connectionUri);

        $this->subscribeClient->on('message', function ($channel, $payload) {
            $this->onMessage($channel, $payload);
        });

        $this->serverId = Str::uuid()->toString();
    }

    /**
     * Get the local connections, regardless of the channel
     * they are connected to.
     *
     * @return \React\Promise\PromiseInterface
     */
    public function getLocalConnections(): PromiseInterface
    {
        return parent::getLocalConnections();
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
        return parent::getLocalChannels($appId);
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
        return $this->getPublishClient()->smembers(
            $this->getRedisKey($appId, null, ['channels'])
        );
    }

    /**
     * Remove connection from all channels.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function unsubscribeFromAllChannels(ConnectionInterface $connection)
    {
        $this->getGlobalChannels($connection->app->id)
            ->then(function ($channels) use ($connection) {
                foreach ($channels as $channel) {
                    $this->unsubscribeFromChannel(
                        $connection, $channel, new stdClass
                    );
                }
            })->then(function () use ($connection) {
                parent::unsubscribeFromAllChannels($connection);
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
        $this->getGlobalConnectionsCount($connection->app->id, $channelName)
            ->then(function ($count) use ($connection, $channelName) {
                if ($count === 0) {
                    $this->subscribeToTopic($connection->app->id, $channelName);
                }
            });

        $this->addConnectionToSet($connection);

        $this->addChannelToSet(
            $connection->app->id, $channelName
        );

        $this->incrementSubscriptionsCount(
            $connection->app->id, $channelName, 1
        );

        parent::subscribeToChannel($connection, $channelName, $payload);
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
        $this->getGlobalConnectionsCount($connection->app->id, $channelName)
            ->then(function ($count) use ($connection, $channelName) {
                if ($count === 0) {
                    $this->unsubscribeFromTopic($connection->app->id, $channelName);

                    $this->removeUserData(
                        $connection->app->id, $channelName, $connection->socketId
                    );

                    $this->removeChannelFromSet($connection->app->id, $channelName);

                    $this->removeConnectionFromSet($connection);

                    return;
                }

                $this->decrementSubscriptionsCount(
                    $connection->app->id, $channelName,
                )
                ->then(function ($count) use ($connection, $channelName) {
                    if ($count < 1) {
                        $this->unsubscribeFromTopic($connection->app->id, $channelName);

                        $this->removeUserData(
                            $connection->app->id, $channelName, $connection->socketId
                        );

                        $this->removeChannelFromSet($connection->app->id, $channelName);

                        $this->removeConnectionFromSet($connection);
                    }
                });
            });

        parent::unsubscribeFromChannel($connection, $channelName, $payload);
    }

    /**
     * Subscribe the connection to a specific channel.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function subscribeToApp($appId)
    {
        $this->subscribeToTopic($appId);

        $this->incrementSubscriptionsCount($appId);
    }

    /**
     * Unsubscribe the connection from the channel.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function unsubscribeFromApp($appId)
    {
        $this->unsubscribeFromTopic($appId);

        $this->incrementSubscriptionsCount($appId, null, -1);
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
        return parent::getLocalConnectionsCount($appId, $channelName);
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
        return $this->publishClient
            ->hget($this->getRedisKey($appId, $channelName, ['stats']), 'connections')
            ->then(function ($count) {
                return is_null($count) ? 0 : (int) $count;
            });
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
        $payload->appId = $appId;
        $payload->serverId = $this->getServerId();

        $this->publishClient->publish($this->getRedisKey($appId, $channel), json_encode($payload));

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
        $this->storeUserData(
            $connection->app->id, $channel, $connection->socketId, json_encode($user)
        );
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
        $this->removeUserData(
            $connection->app->id, $channel, $connection->socketId
        );
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
        return $this->publishClient
            ->hgetall($this->getRedisKey($appId, $channel, ['users']))
            ->then(function ($list) {
                return collect(Helpers::redisListToArray($list))
                    ->map(function ($user) {
                        return json_decode($user);
                    })
                    ->toArray();
            });
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
        return $this->publishClient->hget(
            $this->getRedisKey($connection->app->id, $channel, ['users']), $connection->socketId
        );
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
        $this->publishClient->multi();

        foreach ($channelNames as $channel) {
            $this->publishClient->hlen(
                $this->getRedisKey($appId, $channel, ['users'])
            );
        }

        return $this->publishClient
            ->exec()
            ->then(function ($data) use ($channelNames) {
                return array_combine($channelNames, $data);
            });
    }

    /**
     * Keep tracking the connections availability when they pong.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return bool
     */
    public function connectionPonged(ConnectionInterface $connection): bool
    {
        // This will update the score with the current timestamp.
        $this->addConnectionToSet($connection);

        return parent::connectionPonged($connection);
    }

    /**
     * Remove the obsolete connections that didn't ponged in a while.
     *
     * @return bool
     */
    public function removeObsoleteConnections(): bool
    {
        $this->lock()->get(function () {
            $this->getConnectionsFromSet(0, now()->subMinutes(2)->format('U'))
                ->then(function ($connections) {
                    foreach ($connections as $connection => $score) {
                        [$appId, $socketId] = explode(':', $connection);

                        $this->unsubscribeFromAllChannels(
                            $this->fakeConnectionForApp($appId, $socketId)
                        );
                    }
                });
        });

        return parent::removeObsoleteConnections();
    }

    /**
     * Handle a message received from Redis on a specific channel.
     *
     * @param  string  $redisChannel
     * @param  string  $payload
     * @return void
     */
    public function onMessage(string $redisChannel, string $payload)
    {
        $payload = json_decode($payload);

        if (isset($payload->serverId) && $this->getServerId() === $payload->serverId) {
            return;
        }

        $payload->channel = Str::after($redisChannel, "{$payload->appId}:");

        if (! $channel = $this->find($payload->appId, $payload->channel)) {
            return;
        }

        $appId = $payload->appId ?? null;
        $socketId = $payload->socketId ?? null;
        $serverId = $payload->serverId ?? null;

        unset($payload->socketId);
        unset($payload->serverId);
        unset($payload->appId);

        $channel->broadcastToEveryoneExcept($payload, $socketId, $appId, false);
    }

    /**
     * Build the Redis connection URL from Laravel database config.
     *
     * @return string
     */
    protected function getConnectionUri()
    {
        $name = config('websockets.replication.redis.connection', 'default');
        $config = config("database.redis.{$name}");

        $host = $config['host'];
        $port = $config['port'] ?: 6379;

        $query = [];

        if ($config['password']) {
            $query['password'] = $config['password'];
        }

        if ($config['database']) {
            $query['database'] = $config['database'];
        }

        $query = http_build_query($query);

        return "redis://{$host}:{$port}".($query ? "?{$query}" : '');
    }

    /**
     * Get the Subscribe client instance.
     *
     * @return Client
     */
    public function getSubscribeClient()
    {
        return $this->subscribeClient;
    }

    /**
     * Get the Publish client instance.
     *
     * @return Client
     */
    public function getPublishClient()
    {
        return $this->publishClient;
    }

    /**
     * Get the unique identifier for the server.
     *
     * @return string
     */
    public function getServerId()
    {
        return $this->serverId;
    }

    /**
     * Increment the subscribed count number.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     * @param  int  $increment
     * @return PromiseInterface
     */
    public function incrementSubscriptionsCount($appId, string $channel = null, int $increment = 1)
    {
        return $this->publishClient->hincrby(
            $this->getRedisKey($appId, $channel, ['stats']), 'connections', $increment
        );
    }

    /**
     * Decrement the subscribed count number.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     * @param  int  $decrement
     * @return PromiseInterface
     */
    public function decrementSubscriptionsCount($appId, string $channel = null, int $increment = 1)
    {
        return $this->incrementSubscriptionsCount($appId, $channel, $increment * -1);
    }

    /**
     * Add the connection to the sorted list.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \DateTime|string|null  $moment
     * @return void
     */
    public function addConnectionToSet(ConnectionInterface $connection, $moment = null)
    {
        $this->getPublishClient()
            ->zadd(
                $this->getRedisKey(null, null, ['sockets']),
                Carbon::parse($moment)->format('U'), "{$connection->app->id}:{$connection->socketId}"
            );
    }

    /**
     * Remove the connection from the sorted list.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function removeConnectionFromSet(ConnectionInterface $connection)
    {
        $this->getPublishClient()
            ->zrem(
                $this->getRedisKey(null, null, ['sockets']),
                "{$connection->app->id}:{$connection->socketId}"
            );
    }

    /**
     * Get the connections from the sorted list, with last
     * connection between certain timestamps.
     *
     * @param  int  $start
     * @param  int  $stop
     * @return PromiseInterface
     */
    public function getConnectionsFromSet(int $start = 0, int $stop = 0)
    {
        return $this->getPublishClient()
            ->zrange(
                $this->getRedisKey(null, null, ['sockets']),
                $start, $stop, 'withscores'
            )
            ->then(function ($list) {
                return Helpers::redisListToArray($list);
            });
    }

    /**
     * Add a channel to the set list.
     *
     * @param  string|int  $appId
     * @param  string $channel
     * @return PromiseInterface
     */
    public function addChannelToSet($appId, string $channel)
    {
        return $this->getPublishClient()->sadd(
            $this->getRedisKey($appId, null, ['channels']),
            $channel
        );
    }

    /**
     * Remove a channel from the set list.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @return PromiseInterface
     */
    public function removeChannelFromSet($appId, string $channel)
    {
        return $this->getPublishClient()->srem(
            $this->getRedisKey($appId, null, ['channels']),
            $channel
        );
    }

    /**
     * Set data for a topic. Might be used for the presence channels.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     * @param  string  $key
     * @param  mixed  $data
     * @return PromiseInterface
     */
    public function storeUserData($appId, string $channel = null, string $key, $data)
    {
        $this->publishClient->hset(
            $this->getRedisKey($appId, $channel, ['users']), $key, $data
        );
    }

    /**
     * Remove data for a topic. Might be used for the presence channels.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     * @param  string  $key
     * @return PromiseInterface
     */
    public function removeUserData($appId, string $channel = null, string $key)
    {
        return $this->publishClient->hdel(
            $this->getRedisKey($appId, $channel), $key
        );
    }

    /**
     * Subscribe to the topic for the app, or app and channel.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     * @return void
     */
    public function subscribeToTopic($appId, string $channel = null)
    {
        $this->subscribeClient->subscribe(
            $this->getRedisKey($appId, $channel)
        );
    }

    /**
     * Unsubscribe from the topic for the app, or app and channel.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     * @return void
     */
    public function unsubscribeFromTopic($appId, string $channel = null)
    {
        $this->subscribeClient->unsubscribe(
            $this->getRedisKey($appId, $channel)
        );
    }

    /**
     * Get the Redis Keyspace name to handle subscriptions
     * and other key-value sets.
     *
     * @param  string|int|null  $appId
     * @param  string|null  $channel
     * @return string
     */
    public function getRedisKey($appId = null, string $channel = null, array $suffixes = []): string
    {
        $prefix = config('database.redis.options.prefix', null);

        $hash = "{$prefix}{$appId}";

        if ($channel) {
            $hash .= ":{$channel}";
        }

        $suffixes = join(':', $suffixes);

        if ($suffixes) {
            $hash .= $suffixes;
        }

        return $hash;
    }

    /**
     * Get a new RedisLock instance to avoid race conditions.
     *
     * @return \Illuminate\Cache\CacheLock
     */
    protected function lock()
    {
        return new RedisLock($this->redis, static::$redisLockName, 0);
    }

    /**
     * Create a fake connection for app that will mimick a connection
     * by app ID and Socket ID to be able to be passed to the methods
     * that accepts a connection class.
     *
     * @param  string|int  $appId
     * @param  string  $socketId
     * @return ConnectionInterface
     */
    public function fakeConnectionForApp($appId, string $socketId)
    {
        return new MockableConnection($appId, $socketId);
    }
}
