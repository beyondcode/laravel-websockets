<?php

namespace BeyondCode\LaravelWebSockets\ChannelManagers;

use BeyondCode\LaravelWebSockets\DashboardLogger;
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
     * Create a new channel manager instance.
     *
     * @param  LoopInterface  $loop
     * @param  string|null  $factoryClass
     * @return void
     */
    public function __construct(LoopInterface $loop, $factoryClass = null)
    {
        parent::__construct($loop, $factoryClass);

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
        return $this->publishClient->smembers(
            $this->getChannelsRedisHash($appId)
        );
    }

    /**
     * Remove connection from all channels.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return PromiseInterface[bool]
     */
    public function unsubscribeFromAllChannels(ConnectionInterface $connection): PromiseInterface
    {
        return $this->getGlobalChannels($connection->app->id)
            ->then(function ($channels) use ($connection) {
                foreach ($channels as $channel) {
                    $this->unsubscribeFromChannel($connection, $channel, new stdClass);
                }
            })
            ->then(function () use ($connection) {
                return parent::unsubscribeFromAllChannels($connection);
            });
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
        return $this->subscribeToTopic($connection->app->id, $channelName)
            ->then(function () use ($connection) {
                return $this->addConnectionToSet($connection, Carbon::now());
            })
            ->then(function () use ($connection, $channelName) {
                return $this->addChannelToSet($connection->app->id, $channelName);
            })
            ->then(function () use ($connection, $channelName) {
                return $this->incrementSubscriptionsCount($connection->app->id, $channelName, 1);
            })
            ->then(function () use ($connection, $channelName, $payload) {
                return parent::subscribeToChannel($connection, $channelName, $payload);
            });
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
        return parent::unsubscribeFromChannel($connection, $channelName, $payload)
            ->then(function () use ($connection, $channelName) {
                return $this->decrementSubscriptionsCount($connection->app->id, $channelName);
            })
            ->then(function ($count) use ($connection, $channelName) {
                $this->removeConnectionFromSet($connection);
                // If the total connections count gets to 0 after unsubscribe,
                // try again to check & unsubscribe from the PubSub topic if needed.
                if ($count < 1) {
                    $this->removeChannelFromSet($connection->app->id, $channelName);
                    $this->unsubscribeFromTopic($connection->app->id, $channelName);
                }
            });
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
        return $this->subscribeToTopic($appId)
            ->then(function () use ($appId) {
                return $this->incrementSubscriptionsCount($appId);
            });
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
        return $this->unsubscribeFromTopic($appId)
            ->then(function () use ($appId) {
                return $this->decrementSubscriptionsCount($appId);
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
        return $this->publishClient
            ->hget($this->getStatsRedisHash($appId, $channelName), 'connections')
            ->then(function ($count) {
                return is_null($count) ? 0 : (int) $count;
            });
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
        $payload->appId = $appId;
        $payload->socketId = $socketId;
        $payload->serverId = $serverId ?: $this->getServerId();

        return $this->publishClient
            ->publish($this->getRedisTopicName($appId, $channel), json_encode($payload))
            ->then(function () use ($appId, $socketId, $channel, $payload, $serverId) {
                return parent::broadcastAcrossServers($appId, $socketId, $channel, $payload, $serverId);
            });
    }

    /**
     * Handle the user when it joined a presence channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  stdClass  $user
     * @param  string  $channel
     * @param  stdClass  $payload
     * @return PromiseInterface
     */
    public function userJoinedPresenceChannel(ConnectionInterface $connection, stdClass $user, string $channel, stdClass $payload): PromiseInterface
    {
        return $this->storeUserData($connection->app->id, $channel, $connection->socketId, json_encode($user))
            ->then(function () use ($connection, $channel, $user) {
                return $this->addUserSocket($connection->app->id, $channel, $user, $connection->socketId);
            })
            ->then(function () use ($connection, $user, $channel, $payload) {
                return parent::userJoinedPresenceChannel($connection, $user, $channel, $payload);
            });
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
        return $this->removeUserData($connection->app->id, $channel, $connection->socketId)
            ->then(function () use ($connection, $channel, $user) {
                return $this->removeUserSocket($connection->app->id, $channel, $user, $connection->socketId);
            })
            ->then(function () use ($connection, $user, $channel) {
                return parent::userLeftPresenceChannel($connection, $user, $channel);
            });
    }

    /**
     * Get the presence channel members.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @return \React\Promise\PromiseInterface[array]
     */
    public function getChannelMembers($appId, string $channel): PromiseInterface
    {
        return $this->publishClient
            ->hgetall($this->getUsersRedisHash($appId, $channel))
            ->then(function ($list) {
                return collect(Helpers::redisListToArray($list))->map(function ($user) {
                    return json_decode($user);
                })->unique('user_id')->toArray();
            });
    }

    /**
     * Get a member from a presence channel based on connection.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  string  $channel
     * @return \React\Promise\PromiseInterface[null|array]
     */
    public function getChannelMember(ConnectionInterface $connection, string $channel): PromiseInterface
    {
        return $this->publishClient->hget(
            $this->getUsersRedisHash($connection->app->id, $channel), $connection->socketId
        );
    }

    /**
     * Get the presence channels total members count.
     *
     * @param  string|int  $appId
     * @param  array  $channelNames
     * @return \React\Promise\PromiseInterface[array]
     */
    public function getChannelsMembersCount($appId, array $channelNames): PromiseInterface
    {
        $this->publishClient->multi();

        foreach ($channelNames as $channel) {
            $this->publishClient->hlen(
                $this->getUsersRedisHash($appId, $channel)
            );
        }

        return $this->publishClient->exec()
            ->then(function ($data) use ($channelNames) {
                return array_combine($channelNames, $data);
            });
    }

    /**
     * Get the socket IDs for a presence channel member.
     *
     * @param  string|int  $userId
     * @param  string|int  $appId
     * @param  string  $channelName
     * @return \React\Promise\PromiseInterface[array]
     */
    public function getMemberSockets($userId, $appId, $channelName): PromiseInterface
    {
        return $this->publishClient->smembers(
            $this->getUserSocketsRedisHash($appId, $channelName, $userId)
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
        // This will update the score with the current timestamp.
        return $this->addConnectionToSet($connection, Carbon::now())
            ->then(function () use ($connection) {
                $payload = [
                    'socketId' => $connection->socketId,
                    'appId' => $connection->app->id,
                    'serverId' => $this->getServerId(),
                ];

                return $this->publishClient
                    ->publish($this->getPongRedisHash($connection->app->id), json_encode($payload));
            })
            ->then(function () use ($connection) {
                return parent::connectionPonged($connection);
            });
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
            $lock->get(function () {
                $this->getConnectionsFromSet(0, now()->subMinutes(2)->format('U'))
                    ->then(function ($connections) {
                        foreach ($connections as $socketId => $appId) {
                            $connection = $this->fakeConnectionForApp($appId, $socketId);

                            $this->unsubscribeFromAllChannels($connection);
                        }
                    });
            });

            return parent::removeObsoleteConnections();
        } finally {
            optional($lock)->forceRelease();
        }
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

        if ($redisChannel == $this->getPongRedisHash($payload->appId)) {
            $connection = $this->fakeConnectionForApp($payload->appId, $payload->socketId);

            return parent::connectionPonged($connection);
        }

        $payload->channel = Str::after($redisChannel, "{$payload->appId}:");

        if (! $channel = $this->find($payload->appId, $payload->channel)) {
            return;
        }

        $appId = $payload->appId ?? null;
        $socketId = $payload->socketId ?? null;
        $serverId = $payload->serverId ?? null;

        DashboardLogger::log($appId, DashboardLogger::TYPE_REPLICATOR_MESSAGE_RECEIVED, [
            'fromServerId' => $serverId,
            'fromSocketId' => $socketId,
            'receiverServerId' => $this->getServerId(),
            'channel' => $channel,
            'payload' => $payload,
        ]);

        unset($payload->socketId);
        unset($payload->serverId);
        unset($payload->appId);

        $channel->broadcastLocallyToEveryoneExcept($payload, $socketId, $appId);
    }

    public function find($appId, string $channel)
    {
        if (! $channelInstance = parent::find($appId, $channel)) {
            $class = $this->getChannelClassName($channel);
            $this->channels[$appId][$channel] = new $class($channel);
        }

        return parent::find($appId, $channel);
    }

    /**
     * Build the Redis connection URL from Laravel database config.
     *
     * @return string
     */
    protected function getConnectionUri()
    {
        $name = config('websockets.replication.modes.redis.connection', 'default');
        $config = config("database.redis.{$name}");

        $host = $config['host'];
        $port = $config['port'] ?: 6379;

        $query = [];

        if ($config['password']) {
            $query['password'] = $config['password'];
        }

        if ($config['database']) {
            $query['db'] = $config['database'];
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
     * Get the Redis client used by other classes.
     *
     * @return Client
     */
    public function getRedisClient()
    {
        return $this->getPublishClient();
    }

    /**
     * Increment the subscribed count number.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     * @param  int  $increment
     * @return PromiseInterface[int]
     */
    public function incrementSubscriptionsCount($appId, string $channel = null, int $increment = 1): PromiseInterface
    {
        return $this->publishClient->hincrby(
            $this->getStatsRedisHash($appId, $channel), 'connections', $increment
        );
    }

    /**
     * Decrement the subscribed count number.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     * @param  int  $decrement
     * @return PromiseInterface[int]
     */
    public function decrementSubscriptionsCount($appId, string $channel = null, int $increment = 1): PromiseInterface
    {
        return $this->incrementSubscriptionsCount($appId, $channel, $increment * -1);
    }

    /**
     * Add the connection to the sorted list.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \DateTime|string|null  $moment
     * @return PromiseInterface
     */
    public function addConnectionToSet(ConnectionInterface $connection, $moment = null): PromiseInterface
    {
        $moment = $moment ? Carbon::parse($moment) : Carbon::now();

        return $this->publishClient->zadd(
            $this->getSocketsRedisHash(),
            $moment->format('U'), "{$connection->app->id}:{$connection->socketId}"
        );
    }

    /**
     * Remove the connection from the sorted list.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return PromiseInterface
     */
    public function removeConnectionFromSet(ConnectionInterface $connection): PromiseInterface
    {
        return $this->publishClient->zrem(
            $this->getSocketsRedisHash(),
            "{$connection->app->id}:{$connection->socketId}"
        );
    }

    /**
     * Get the connections from the sorted list, with last
     * connection between certain timestamps.
     *
     * @param  int  $start
     * @param  int  $stop
     * @param  bool  $strict
     * @return PromiseInterface[array]
     */
    public function getConnectionsFromSet(int $start = 0, int $stop = 0, bool $strict = true): PromiseInterface
    {
        if ($strict) {
            $start = "({$start}";
            $stop = "({$stop}";
        }

        return $this->publishClient
            ->zrangebyscore($this->getSocketsRedisHash(), $start, $stop)
            ->then(function ($list) {
                return collect($list)->mapWithKeys(function ($appWithSocket) {
                    [$appId, $socketId] = explode(':', $appWithSocket);

                    return [$socketId => $appId];
                })->toArray();
            });
    }

    /**
     * Add a channel to the set list.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @return PromiseInterface
     */
    public function addChannelToSet($appId, string $channel): PromiseInterface
    {
        return $this->publishClient->sadd(
            $this->getChannelsRedisHash($appId), $channel
        );
    }

    /**
     * Remove a channel from the set list.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @return PromiseInterface
     */
    public function removeChannelFromSet($appId, string $channel): PromiseInterface
    {
        return $this->publishClient->srem(
            $this->getChannelsRedisHash($appId), $channel
        );
    }

    /**
     * Check if channel is on the list.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @return PromiseInterface
     */
    public function isChannelInSet($appId, string $channel): PromiseInterface
    {
        return $this->publishClient->sismember(
            $this->getChannelsRedisHash($appId), $channel
        );
    }

    /**
     * Set data for a topic. Might be used for the presence channels.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     * @param  string  $key
     * @param  string  $data
     * @return PromiseInterface
     */
    public function storeUserData($appId, string $channel = null, string $key, $data): PromiseInterface
    {
        return $this->publishClient->hset(
            $this->getUsersRedisHash($appId, $channel), $key, $data
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
    public function removeUserData($appId, string $channel = null, string $key): PromiseInterface
    {
        return $this->publishClient->hdel(
            $this->getUsersRedisHash($appId, $channel), $key
        );
    }

    /**
     * Subscribe to the topic for the app, or app and channel.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     * @return PromiseInterface
     */
    public function subscribeToTopic($appId, string $channel = null): PromiseInterface
    {
        $topic = $this->getRedisTopicName($appId, $channel);

        DashboardLogger::log($appId, DashboardLogger::TYPE_REPLICATOR_SUBSCRIBED, [
            'serverId' => $this->getServerId(),
            'pubsubTopic' => $topic,
        ]);

        return $this->subscribeClient->subscribe($topic);
    }

    /**
     * Unsubscribe from the topic for the app, or app and channel.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     * @return PromiseInterface
     */
    public function unsubscribeFromTopic($appId, string $channel = null): PromiseInterface
    {
        $topic = $this->getRedisTopicName($appId, $channel);

        DashboardLogger::log($appId, DashboardLogger::TYPE_REPLICATOR_UNSUBSCRIBED, [
            'serverId' => $this->getServerId(),
            'pubsubTopic' => $topic,
        ]);

        return $this->subscribeClient->unsubscribe($topic);
    }

    /**
     * Add the Presence Channel's User's Socket ID to a list.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @param  stdClass  $user
     * @param  string  $socketId
     * @return PromiseInterface
     */
    protected function addUserSocket($appId, string $channel, stdClass $user, string $socketId): PromiseInterface
    {
        return $this->publishClient->sadd(
            $this->getUserSocketsRedisHash($appId, $channel, $user->user_id), $socketId
        );
    }

    /**
     * Remove the Presence Channel's User's Socket ID from the list.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @param  stdClass  $user
     * @param  string  $socketId
     * @return PromiseInterface
     */
    protected function removeUserSocket($appId, string $channel, stdClass $user, string $socketId): PromiseInterface
    {
        return $this->publishClient->srem(
            $this->getUserSocketsRedisHash($appId, $channel, $user->user_id), $socketId
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
            $suffixes = array_merge([$channel], $suffixes);
        }

        $suffixes = implode(':', $suffixes);

        if ($suffixes) {
            $hash .= ":{$suffixes}";
        }

        return $hash;
    }

    /**
     * Get the pong Redis hash.
     *
     * @param  string|int  $appId
     */
    public function getPongRedisHash($appId): string
    {
        return $this->getRedisKey($appId, null, ['pong']);
    }

    /**
     * Get the statistics Redis hash.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     * @return string
     */
    public function getStatsRedisHash($appId, string $channel = null): string
    {
        return $this->getRedisKey($appId, $channel, ['stats']);
    }

    /**
     * Get the sockets Redis hash used to store all sockets ids.
     *
     * @return string
     */
    public function getSocketsRedisHash(): string
    {
        return $this->getRedisKey(null, null, ['sockets']);
    }

    /**
     * Get the channels Redis hash for a specific app id, used
     * to store existing channels.
     *
     * @param  string|int  $appId
     * @return string
     */
    public function getChannelsRedisHash($appId): string
    {
        return $this->getRedisKey($appId, null, ['channels']);
    }

    /**
     * Get the Redis hash for storing presence channels users.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     * @return string
     */
    public function getUsersRedisHash($appId, string $channel = null): string
    {
        return $this->getRedisKey($appId, $channel, ['users']);
    }

    /**
     * Get the Redis hash for storing socket ids
     * for a specific presence channels user.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     * @param  string|int|null  $userId
     * @return string
     */
    public function getUserSocketsRedisHash($appId, string $channel = null, $userId = null): string
    {
        return $this->getRedisKey($appId, $channel, [$userId, 'userSockets']);
    }

    /**
     * Get the Redis topic name for PubSub
     * used to transfer info between servers.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     * @return string
     */
    public function getRedisTopicName($appId, string $channel = null): string
    {
        return $this->getRedisKey($appId, $channel);
    }

    /**
     * Get a new RedisLock instance to avoid race conditions.
     *
     * @return \Illuminate\Cache\CacheLock
     */
    protected function lock()
    {
        return new RedisLock($this->redis, static::$lockName, 0);
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
