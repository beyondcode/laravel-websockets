<?php

namespace BeyondCode\LaravelWebSockets\ChannelManagers;

use Amp\LazyPromise;
use Amp\Promise;
use Amp\Redis\Config;
use Amp\Redis\Redis as AmpRedis;
use Amp\Redis\RemoteExecutor;
use Amp\Redis\Subscriber;
use Amp\Redis\Subscription;
use BeyondCode\LaravelWebSockets\Contracts\Connection;
use BeyondCode\LaravelWebSockets\DashboardLogger;
use BeyondCode\LaravelWebSockets\Helpers;
use BeyondCode\LaravelWebSockets\Server\MockableConnection;
use DateTimeInterface;
use Illuminate\Cache\RedisLock;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class RedisChannelManager extends LocalChannelManager
{
    /**
     * The pub client.
     *
     * @var \Amp\Redis\Redis
     */
    protected $publishClient;

    /**
     * The sub client.
     *
     * @var \Amp\Redis\Subscriber
     */
    protected $subscribeClient;

    /**
     * The Redis manager instance.
     *
     * @var \Illuminate\Redis\RedisManager
     */
    protected $redis;

    /**
     * @var array|\Amp\Redis\Subscription[]
     */
    protected $subscriptions = [];

    /**
     * Create a new channel manager instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->redis = Redis::connection(
            config('websockets.replication.modes.redis.connection', 'default')
        );

        $connection = new RemoteExecutor($config = Config::fromUri($this->getConnectionUri()));

        $this->publishClient = new AmpRedis($connection);
        $this->subscribeClient = new Subscriber($config);

        $this->subscribeClient->subscribe('message')->onResolve(
            function ($channel, $payload) {
                $this->onMessage($channel, $payload);
            }
        );
    }

    /**
     * Get all channels for a specific app across multiple servers.
     *
     * @param  string|int  $appId
     *
     * @return \Amp\Promise<array>
     */
    public function getGlobalChannels($appId): Promise
    {
        return $this->publishClient->getSet($this->getChannelsRedisHash($appId))->getAll();
    }

    /**
     * Remove connection from all channels.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     *
     * @return \Amp\Promise<bool>
     */
    public function unsubscribeFromAllChannels(Connection $connection): Promise
    {
        $globalChannels = $this->getGlobalChannels($connection->getAppId());

        $globalChannels->onResolve(
            function ($channels) use ($connection): void {
                foreach ($channels as $channel) {
                    $this->unsubscribeFromChannel($connection, $channel, []);
                }
            }
        );

        $globalChannels->onResolve(
            function () use ($connection): Promise {
                return parent::unsubscribeFromAllChannels($connection);
            }
        );

        return $globalChannels;
    }

    /**
     * Subscribe the connection to a specific channel.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  string  $channelName
     * @param  object|array  $payload
     *
     * @return \Amp\Promise<bool>
     */
    public function subscribeToChannel(Connection $connection, string $channelName, $payload): Promise
    {
        $topic = $this->subscribeToTopic($connection->getAppId(), $channelName);

        $topic->onResolve(
            function () use ($connection) {
                return $this->addConnectionToSet($connection);
            }
        );

        $topic->onResolve(
            function () use ($connection, $channelName) {
                return $this->addChannelToSet($connection->getAppId(), $channelName);
            }
        );

        $topic->onResolve(
            function () use ($connection, $channelName) {
                return $this->incrementSubscriptionsCount($connection->getAppId(), $channelName);
            }
        );

        $topic->onResolve(
            function () use ($connection, $channelName, $payload) {
                return parent::subscribeToChannel($connection, $channelName, $payload);
            }
        );

        return $topic;
    }

    /**
     * Unsubscribe the connection from the channel.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  string  $channelName
     * @param  object|array  $payload
     *
     * @return \Amp\Promise<bool>
     */
    public function unsubscribeFromChannel(Connection $connection, string $channelName, $payload): Promise
    {
        $count = $this->getGlobalConnectionsCount($connection->getAppId(), $channelName);

        $count->onResolve(
            function ($count) use ($connection, $channelName) {
                if ($count === 0) {
                    // Make sure to not stay subscribed to the PubSub topic
                    // if there are no connections.
                    $this->unsubscribeFromTopic($connection->getAppId(), $channelName);
                }

                $this->decrementSubscriptionsCount($connection->getAppId(), $channelName)
                    ->onResolve(
                        function ($count) use ($connection, $channelName) {
                            // If the total connections count gets to 0 after unsubscribe,
                            // try again to check & unsubscribe from the PubSub topic if needed.
                            if ($count < 1) {
                                $this->unsubscribeFromTopic($connection->getAppId(), $channelName);
                            }
                        }
                    );
            }
        );
        $count->onResolve(
            function () use ($connection, $channelName) {
                return $this->removeChannelFromSet($connection->getAppId(), $channelName);
            }
        );

        $count->onResolve(
            function () use ($connection) {
                return $this->removeConnectionFromSet($connection);
            }
        );
        $count->onResolve(
            function () use ($connection, $channelName, $payload) {
                return parent::unsubscribeFromChannel($connection, $channelName, $payload);
            }
        );

        return $count;
    }

    /**
     * Subscribe the connection to a specific channel, returning
     * a promise containing the amount of connections.
     *
     * @param  string|int  $appId
     *
     * @return \Amp\Promise<int>
     */
    public function subscribeToApp($appId): Promise
    {
        $subscription = $this->subscribeToTopic($appId);

        $subscription->onResolve(
            function () use ($appId) {
                return $this->incrementSubscriptionsCount($appId);
            }
        );

        return $subscription;
    }

    /**
     * Unsubscribe the connection from the channel, returning
     * a promise containing the amount of connections after decrement.
     *
     * @param  string|int  $appId
     *
     * @return \Amp\Promise<int>
     */
    public function unsubscribeFromApp($appId): Promise
    {
        $unsubscription = $this->unsubscribeFromTopic($appId);

        $unsubscription->onResolve(
            function () use ($appId) {
                return $this->decrementSubscriptionsCount($appId);
            }
        );

        return $unsubscription;
    }

    /**
     * Get the connections count
     * across multiple servers.
     *
     * @param  string|int  $appId
     * @param  string|null  $channelName
     *
     * @return \Amp\Promise<int>
     */
    public function getGlobalConnectionsCount($appId, string $channelName = null): Promise
    {
        $globalConnections = $this->publishClient
            ->getMap($this->getStatsRedisHash($appId, $channelName))
            ->getValue('connections');

        $globalConnections->onResolve(
            function ($count) {
                return (int)$count;
            }
        );

        return $globalConnections;
    }

    /**
     * Broadcast the message across multiple servers.
     *
     * @param  string|int  $appId
     * @param  string|null  $socketId
     * @param  string  $channel
     * @param  object|array  $payload
     * @param  string|null  $serverId
     *
     * @return \Amp\Promise<bool>
     */
    public function broadcastAcrossServers(
        $appId,
        ?string $socketId,
        string $channel,
        $payload,
        string $serverId = null
    ): Promise {
        $payload->appId = $appId;
        $payload->socketId = $socketId;
        $payload->serverId = $serverId ?: $this->getServerId();

        $publish = $this->publishClient
            ->publish($this->getRedisTopicName($appId, $channel), json_encode($payload));

        $publish->onResolve(
            function () use ($appId, $socketId, $channel, $payload, $serverId) {
                return parent::broadcastAcrossServers($appId, $socketId, $channel, $payload, $serverId);
            }
        );

        return $publish;
    }

    /**
     * Handle the user when it joined a presence channel.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  object  $user
     * @param  string  $channel
     * @param  object|array  $payload
     *
     * @return \Amp\Promise<bool>
     */
    public function userJoinedPresenceChannel(Connection $connection, object $user, string $channel, $payload): Promise
    {
        $stored = $this->storeUserData($connection->getAppId(), $channel, $connection->getId(), json_encode($user));

        $stored->onResolve(
            function () use ($connection, $channel, $user) {
                return $this->addUserSocket($connection->getAppId(), $channel, $user, $connection->getId());
            }
        );

        $stored->onResolve(
            function () use ($connection, $user, $channel, $payload) {
                return parent::userJoinedPresenceChannel($connection, $user, $channel, $payload);
            }
        );

        return $stored;
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
        $remove = $this->removeUserData($connection->getAppId(), $channel, $connection->getId());

        $remove->onResolve(
            function () use ($connection, $channel, $user) {
                return $this->removeUserSocket($connection->getAppId(), $channel, $user, $connection->getId());
            }
        );

        $remove->onResolve(
            function () use ($connection, $user, $channel) {
                return parent::userLeftPresenceChannel($connection, $user, $channel);
            }
        );

        return $remove;
    }

    /**
     * Get the presence channel members.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     *
     * @return \Amp\Promise<array<object>>
     */
    public function getChannelMembers($appId, string $channel): Promise
    {
        $all = $this->publishClient
            ->getMap($this->getUsersRedisHash($appId, $channel))
            ->getAll();

        $all->onResolve(
            static function ($list): array {
                return collect(Helpers::redisListToArray($list))->map(
                    static function (string $user): object {
                        return json_decode($user);
                    }
                )->unique('user_id')->toArray();
            }
        );

        return $all;
    }

    /**
     * Get a member from a presence channel based on connection.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  string  $channel
     *
     * @return \Amp\Promise<null|array>
     */
    public function getChannelMember(Connection $connection, string $channel): Promise
    {
        return $this->publishClient
            ->getMap($this->getUsersRedisHash($connection->getAppId(), $channel))
            ->getValue($connection->getId());
    }

    /**
     * Get the presence channels total members count.
     *
     * @param  string|int  $appId
     * @param  array  $channelNames
     *
     * @return \Amp\Promise<array>
     */
    public function getChannelsMembersCount($appId, array $channelNames): Promise
    {
        $this->publishClient->query('multi');

        foreach ($channelNames as $channel) {
            $this->publishClient->getMap($this->getUsersRedisHash($appId, $channel))->getSize();
        }

        $query = $this->publishClient->query('exec');

        $query->onResolve(
            function ($data) use ($channelNames) {
                return array_combine($channelNames, $data);
            }
        );

        return $query;
    }

    /**
     * Get the socket IDs for a presence channel member.
     *
     * @param  string|int  $userId
     * @param  string|int  $appId
     * @param  string  $channelName
     *
     * @return \Amp\Promise<array>
     */
    public function getMemberSockets($userId, $appId, string $channelName): Promise
    {
        return $this->publishClient
            ->getSet($this->getUserSocketsRedisHash($appId, $channelName, $userId))
            ->getAll();
    }

    /**
     * Keep tracking the connections availability when they pong.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     *
     * @return \Amp\Promise<bool>
     */
    public function connectionPonged(Connection $connection): Promise
    {
        // This will update the score with the current timestamp.
        $pong = $this->addConnectionToSet($connection, now());

        $pong->onResolve(
            function () use ($connection): Promise {
                return parent::connectionPonged($connection);
            }
        );

        return $pong;
    }

    /**
     * Remove the obsolete connections that didn't ponged in a while.
     *
     * @return \Amp\Promise<bool>
     */
    public function removeObsoleteConnections(): Promise
    {
        $this->lock()->get(
            function (): void {
                $this->getConnectionsFromSet(0, now()->subMinutes(2)->format('U'))
                    ->onResolve(
                        function ($connections): void {
                            foreach ($connections as $socketId => $appId) {
                                $connection = $this->fakeConnectionForApp($appId, $socketId);

                                $this->unsubscribeFromAllChannels($connection);
                            }
                        }
                    );
            }
        );

        return parent::removeObsoleteConnections();
    }

    /**
     * Handle a message received from Redis on a specific channel.
     *
     * @param  string  $redisChannel
     * @param  string  $payload
     *
     * @return void
     */
    public function onMessage(string $redisChannel, string $payload): void
    {
        $payload = json_decode($payload);

        if (isset($payload->serverId) && $this->getServerId() === $payload->serverId) {
            return;
        }

        $payload->channel = Str::after($redisChannel, "{$payload->appId}:");

        if (!$channel = $this->find($payload->appId, $payload->channel)) {
            return;
        }

        $appId = $payload->appId ?? null;
        $socketId = $payload->socketId ?? null;
        $serverId = $payload->serverId ?? null;

        app(DashboardLogger::class)->log(
            $appId,
            DashboardLogger::TYPE_REPLICATOR_MESSAGE_RECEIVED,
            [
                'fromServerId'     => $serverId,
                'fromSocketId'     => $socketId,
                'receiverServerId' => $this->getServerId(),
                'channel'          => $channel,
                'payload'          => $payload,
            ]
        );

        unset($payload->socketId, $payload->serverId, $payload->appId);

        $channel->broadcastLocallyToEveryoneExcept($payload, $socketId, $appId);
    }

    /**
     * Build the Redis connection URL from Laravel database config.
     *
     * @return string
     */
    protected function getConnectionUri(): string
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

        return "redis://{$host}:{$port}" . ($query ? "?{$query}" : '');
    }

    /**
     * Get the Subscribe client instance.
     *
     * @return \Amp\Redis\Subscriber
     */
    public function getSubscribeClient(): Subscriber
    {
        return $this->subscribeClient;
    }

    /**
     * Get the Publish client instance.
     *
     * @return \Amp\Redis\Redis
     */
    public function getPublishClient(): AmpRedis
    {
        return $this->publishClient;
    }

    /**
     * Get the Redis client used by other classes.
     *
     * @return \Amp\Redis\Redis
     */
    public function getRedisClient(): AmpRedis
    {
        return $this->getPublishClient();
    }

    /**
     * Increment the subscribed count number.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     * @param  int  $increment
     *
     * @return \Amp\Promise<int>
     */
    public function incrementSubscriptionsCount($appId, string $channel = null, int $increment = 1): Promise
    {
        return $this->publishClient
            ->getMap($this->getStatsRedisHash($appId, $channel))
            ->increment('connections', $increment);
    }

    /**
     * Decrement the subscribed count number.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     * @param  int  $increment
     *
     * @return \Amp\Promise<int>
     */
    public function decrementSubscriptionsCount($appId, string $channel = null, int $increment = 1): Promise
    {
        return $this->incrementSubscriptionsCount($appId, $channel, $increment * -1);
    }

    /**
     * Add the connection to the sorted list.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  \DateTimeInterface|string|null  $moment
     *
     * @return \Amp\Promise
     */
    public function addConnectionToSet(Connection $connection, $moment = 'now'): Promise
    {
        if (!$moment instanceof DateTimeInterface) {
            $moment = date($moment);
        }

        return $this->publishClient
            ->getSet($this->getSocketsRedisHash())
            ->add($moment->format('U'), "{$connection->getAppId()}:{$connection->getId()}");
    }

    /**
     * Remove the connection from the sorted list.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     *
     * @return \Amp\Promise
     */
    public function removeConnectionFromSet(Connection $connection): Promise
    {
        return $this->publishClient
            ->getSet($this->getSocketsRedisHash())
            ->remove("{$connection->getAppId()}:{$connection->getId()}");
    }

    /**
     * Get the connections from the sorted list, with last
     * connection between certain timestamps.
     *
     * @param  int  $start
     * @param  int  $stop
     * @param  bool  $strict
     *
     * @return \Amp\Promise [array]
     */
    public function getConnectionsFromSet(int $start = 0, int $stop = 0, bool $strict = true): Promise
    {
        if ($strict) {
            $start = "({$start}";
            $stop = "({$stop}";
        }

        // Before, this was "zrangebyscore($this->getSocketsRedisHash(), $start, $stop)"
        // Since the Amphp Redis still doesn't have zrange, we will do it ourselves.
        $query = $this->publishClient
            ->query('zrange', $this->getSocketsRedisHash(), $start, $stop, 'byscore');

        $query
            ->onResolve(
                static function (array $list): array {
                    return collect($list)->mapWithKeys(
                        static function (string $appWithSocket): array {
                            [$appId, $socketId] = explode(':', $appWithSocket);

                            return [$socketId => $appId];
                        }
                    )->toArray();
                }
            );

        return $query;
    }

    /**
     * Add a channel to the set list.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     *
     * @return \Amp\Promise
     */
    public function addChannelToSet($appId, string $channel): Promise
    {
        return $this->publishClient
            ->getSet($this->getChannelsRedisHash($appId))
            ->add($channel);
    }

    /**
     * Remove a channel from the set list.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     *
     * @return Promise
     */
    public function removeChannelFromSet($appId, string $channel): Promise
    {
        return $this->publishClient
            ->getSet($this->getChannelsRedisHash($appId))
            ->remove($channel);
    }

    /**
     * Set data for a topic. Might be used for the presence channels.
     *
     * @param  string|int  $appId
     * @param  string  $key
     * @param  string  $data
     * @param  string|null  $channel
     *
     * @return \Amp\Promise
     */
    public function storeUserData($appId, string $key, string $data, string $channel = null): Promise
    {
        return $this->publishClient
            ->getMap($this->getUsersRedisHash($appId, $channel))
            ->setValue($key, $data);
    }

    /**
     * Remove data for a topic. Might be used for the presence channels.
     *
     * @param  string|int  $appId
     * @param  string  $key
     * @param  string|null  $channel
     *
     * @return \Amp\Promise
     */
    public function removeUserData($appId, string $key, string $channel = null): Promise
    {
        return $this->publishClient
            ->getMap($this->getUsersRedisHash($appId, $channel))
            ->remove($key);
    }

    /**
     * Subscribe to the topic for the app, or app and channel.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     *
     * @return \Amp\Promise<\Amp\Redis\Subscription>
     */
    public function subscribeToTopic($appId, string $channel = null): Promise
    {
        $topic = $this->getRedisTopicName($appId, $channel);

        app(DashboardLogger::class)->log(
            $appId,
            DashboardLogger::TYPE_REPLICATOR_SUBSCRIBED,
            [
                'serverId'    => $this->getServerId(),
                'pubsubTopic' => $topic,
            ]
        );

        $subscription = $this->subscribeClient->subscribe($topic);

        // Add the subscription to this channel manager once it's done.
        $subscription->onResolve(
            function (Subscription $subscription) use ($topic): void {
                $this->subscriptions[$topic] = $subscription;
            }
        );

        return $subscription;
    }

    /**
     * Unsubscribe from the topic for the app, or app and channel.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     *
     * @return \Amp\Promise
     */
    public function unsubscribeFromTopic($appId, string $channel = null): Promise
    {
        $topic = $this->getRedisTopicName($appId, $channel);

        app(DashboardLogger::class)->log(
            $appId,
            DashboardLogger::TYPE_REPLICATOR_UNSUBSCRIBED,
            [
                'serverId'    => $this->getServerId(),
                'pubsubTopic' => $topic,
            ]
        );

        $subscription = $this->subscriptions[$topic];

        unset($this->subscriptions[$topic]);

        return new LazyPromise(
            static function () use ($subscription): Subscription {
                return $subscription;
            }
        );
    }

    /**
     * Add the Presence Channel's User's Socket ID to a list.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @param  object  $user
     * @param  string  $socketId
     *
     * @return \Amp\Promise
     */
    protected function addUserSocket($appId, string $channel, object $user, string $socketId): Promise
    {
        return $this->publishClient
            ->getSet($this->getUserSocketsRedisHash($appId, $channel, $user->user_id))
            ->add($socketId);
    }

    /**
     * Remove the Presence Channel's User's Socket ID from the list.
     *
     * @param  string|int  $appId
     * @param  string  $channel
     * @param  object  $user
     * @param  string  $socketId
     *
     * @return \Amp\Promise
     */
    protected function removeUserSocket($appId, string $channel, object $user, string $socketId): Promise
    {
        return $this->publishClient
            ->getSet($this->getUserSocketsRedisHash($appId, $channel, $user->user_id))
            ->remove($socketId);
    }

    /**
     * Get the Redis Keyspace name to handle subscriptions
     * and other key-value sets.
     *
     * @param  string|int|null  $appId
     * @param  string|null  $channel
     *
     * @return string
     */
    public function getRedisKey($appId = null, string $channel = null, array $suffixes = []): string
    {
        $hash = config('database.redis.options.prefix') . $appId;

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
     * Get the statistics Redis hash.
     *
     * @param  string|int  $appId
     * @param  string|null  $channel
     *
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
     *
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
     *
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
     *
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
     *
     * @return string
     */
    public function getRedisTopicName($appId, string $channel = null): string
    {
        return $this->getRedisKey($appId, $channel);
    }

    /**
     * Get a new RedisLock instance to avoid race conditions.
     *
     * @return \Illuminate\Cache\RedisLock
     */
    protected function lock(): Lock
    {
        return new RedisLock($this->redis, static::$lockName, 0);
    }

    /**
     * Create a fake connection for app that will mimic a connection
     * by app ID and Socket ID to be able to be passed to the methods
     * that accepts a connection class.
     *
     * @param  string|int  $appId
     * @param  string  $socketId
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Connection
     */
    public function fakeConnectionForApp($appId, string $socketId)
    {
        return new MockableConnection($appId, $socketId);
    }
}
