<?php

namespace BeyondCode\LaravelWebSockets\PubSub\Drivers;

use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use stdClass;

class RedisClient extends LocalClient
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
     * Mapping of subscribed channels, where the key is the channel name,
     * and the value is the amount of connections which are subscribed to
     * that channel. Used to keep track of whether we still need to stay
     * subscribed to those channels with Redis.
     *
     * @var int[]
     */
    protected $subscribedChannels = [];

    /**
     * Create a new Redis client.
     *
     * @return void
     */
    public function __construct()
    {
        $this->serverId = Str::uuid()->toString();
        $this->redis = Cache::getRedis();
    }

    /**
     * Boot the RedisClient, initializing the connections.
     *
     * @param  LoopInterface  $loop
     * @param  string|null  $factoryClass
     * @return ReplicationInterface
     */
    public function boot(LoopInterface $loop, $factoryClass = null): ReplicationInterface
    {
        $factoryClass = $factoryClass ?: Factory::class;

        $this->loop = $loop;

        $connectionUri = $this->getConnectionUri();
        $factory = new $factoryClass($this->loop);

        $this->publishClient = $factory->createLazyClient($connectionUri);
        $this->subscribeClient = $factory->createLazyClient($connectionUri);

        // The subscribed client gets a message, it triggers the onMessage().
        $this->subscribeClient->on('message', function ($channel, $payload) {
            $this->onMessage($channel, $payload);
        });

        return $this;
    }

    /**
     * Publish a message to a channel on behalf of a websocket user.
     *
     * @param  string  $appId
     * @param  string  $channel
     * @param  stdClass  $payload
     * @return bool
     */
    public function publish($appId, string $channel, stdClass $payload): bool
    {
        $payload->appId = $appId;
        $payload->serverId = $this->getServerId();

        $payload = json_encode($payload);

        $this->publishClient->__call('publish', [$this->getTopicName($appId, $channel), $payload]);

        DashboardLogger::log($appId, DashboardLogger::TYPE_REPLICATOR_MESSAGE_PUBLISHED, [
            'channel' => $channel,
            'serverId' => $this->getServerId(),
            'payload' => $payload,
            'pubsub' => $this->getTopicName($appId, $channel),
        ]);

        return true;
    }

    /**
     * Subscribe to a channel on behalf of websocket user.
     *
     * @param  string  $appId
     * @param  string  $channel
     * @return bool
     */
    public function subscribe($appId, string $channel): bool
    {
        if (! isset($this->subscribedChannels["{$appId}:{$channel}"])) {
            // We're not subscribed to the channel yet, subscribe and set the count to 1
            $this->subscribeClient->__call('subscribe', [$this->getTopicName($appId, $channel)]);
            $this->subscribedChannels["{$appId}:{$channel}"] = 1;
        } else {
            // Increment the subscribe count if we've already subscribed
            $this->subscribedChannels["{$appId}:{$channel}"]++;
        }

        DashboardLogger::log($appId, DashboardLogger::TYPE_REPLICATOR_SUBSCRIBED, [
            'channel' => $channel,
            'serverId' => $this->getServerId(),
            'pubsub' => $this->getTopicName($appId, $channel),
        ]);

        return true;
    }

    /**
     * Unsubscribe from a channel on behalf of a websocket user.
     *
     * @param  string  $appId
     * @param  string  $channel
     * @return bool
     */
    public function unsubscribe($appId, string $channel): bool
    {
        if (! isset($this->subscribedChannels["{$appId}:{$channel}"])) {
            return false;
        }

        // Decrement the subscription count for this channel
        $this->subscribedChannels["{$appId}:{$channel}"]--;

        // If we no longer have subscriptions to that channel, unsubscribe
        if ($this->subscribedChannels["{$appId}:{$channel}"] < 1) {
            $this->subscribeClient->__call('unsubscribe', [$this->getTopicName($appId, $channel)]);

            unset($this->subscribedChannels["{$appId}:{$channel}"]);
        }

        DashboardLogger::log($appId, DashboardLogger::TYPE_REPLICATOR_UNSUBSCRIBED, [
            'channel' => $channel,
            'serverId' => $this->getServerId(),
            'pubsub' => $this->getTopicName($appId, $channel),
        ]);

        return true;
    }

    /**
     * Subscribe to the app's pubsub keyspace.
     *
     * @param  mixed  $appId
     * @return bool
     */
    public function subscribeToApp($appId): bool
    {
        $this->subscribeClient->__call('subscribe', [$this->getTopicName($appId)]);

        $this->redis->hincrby($this->getTopicName($appId), 'connections', 1);

        return true;
    }

    /**
     * Unsubscribe from the app's pubsub keyspace.
     *
     * @param  mixed  $appId
     * @return bool
     */
    public function unsubscribeFromApp($appId): bool
    {
        $this->subscribeClient->__call('unsubscribe', [$this->getTopicName($appId)]);

        $this->redis->hincrby($this->getTopicName($appId), 'connections', -1);

        return true;
    }

    /**
     * Add a member to a channel. To be called when they have
     * subscribed to the channel.
     *
     * @param  string  $appId
     * @param  string  $channel
     * @param  string  $socketId
     * @param  string  $data
     * @return void
     */
    public function joinChannel($appId, string $channel, string $socketId, string $data)
    {
        $this->redis->hset($this->getTopicName($appId, $channel), $socketId, $data);

        DashboardLogger::log($appId, DashboardLogger::TYPE_REPLICATOR_JOINED_CHANNEL, [
            'channel' => $channel,
            'serverId' => $this->getServerId(),
            'socketId' => $socketId,
            'data' => $data,
            'pubsub' => $this->getTopicName($appId, $channel),
        ]);
    }

    /**
     * Remove a member from the channel. To be called when they have
     * unsubscribed from the channel.
     *
     * @param  string  $appId
     * @param  string  $channel
     * @param  string  $socketId
     * @return void
     */
    public function leaveChannel($appId, string $channel, string $socketId)
    {
        $this->redis->hdel($this->getTopicName($appId, $channel), $socketId);

        DashboardLogger::log($appId, DashboardLogger::TYPE_REPLICATOR_LEFT_CHANNEL, [
            'channel' => $channel,
            'serverId' => $this->getServerId(),
            'socketId' => $socketId,
            'pubsub' => $this->getTopicName($appId, $channel),
        ]);
    }

    /**
     * Retrieve the full information about the members in a presence channel.
     *
     * @param  string  $appId
     * @param  string  $channel
     * @return PromiseInterface
     */
    public function channelMembers($appId, string $channel): PromiseInterface
    {
        return $this->publishClient->__call('hgetall', [$this->getTopicName($appId, $channel)])
            ->then(function ($members) {
                // The data is expected as objects, so we need to JSON decode
                return array_map(function ($user) {
                    return json_decode($user);
                }, $members);
            });
    }

    /**
     * Get the amount of users subscribed for each presence channel.
     *
     * @param  string  $appId
     * @param  array  $channelNames
     * @return PromiseInterface
     */
    public function channelMemberCounts($appId, array $channelNames): PromiseInterface
    {
        $this->publishClient->__call('multi', []);

        foreach ($channelNames as $channel) {
            $this->publishClient->__call('hlen', [$this->getTopicName($appId, $channel)]);
        }

        return $this->publishClient->__call('exec', [])
            ->then(function ($data) use ($channelNames) {
                return array_combine($channelNames, $data);
            });
    }

    /**
     * Get the amount of unique connections.
     *
     * @param  mixed  $appId
     * @return null|int|\React\Promise\PromiseInterface
     */
    public function appConnectionsCount($appId)
    {
        // Use the in-built Redis manager to avoid async run.

        return $this->redis->hget($this->getTopicName($appId), 'connections') ?: 0;
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

        // Ignore messages sent by ourselves.
        if (isset($payload->serverId) && $this->getServerId() === $payload->serverId) {
            return;
        }

        // Pull out the app ID. See RedisPusherBroadcaster
        $appId = $payload->appId;

        // We need to put the channel name in the payload.
        // We strip the app ID from the channel name, websocket clients
        // expect the channel name to not include the app ID.
        $payload->channel = Str::after($redisChannel, "{$appId}:");

        $channelManager = app(ChannelManager::class);

        // Load the Channel instance to sync.
        $channel = $channelManager->find($appId, $payload->channel);

        // If no channel is found, none of our connections want to
        // receive this message, so we ignore it.
        if (! $channel) {
            return;
        }

        $socketId = $payload->socketId ?? null;
        $serverId = $payload->serverId ?? null;

        // Remove fields intended for internal use from the payload.
        unset($payload->socketId);
        unset($payload->serverId);
        unset($payload->appId);

        // Push the message out to connected websocket clients.
        $channel->broadcastToEveryoneExcept($payload, $socketId, $appId, false);

        DashboardLogger::log($appId, DashboardLogger::TYPE_REPLICATOR_MESSAGE_RECEIVED, [
            'channel' => $channel->getChannelName(),
            'redisChannel' => $redisChannel,
            'serverId' => $this->getServerId(),
            'incomingServerId' => $serverId,
            'incomingSocketId' => $socketId,
            'payload' => $payload,
        ]);
    }

    /**
     * Build the Redis connection URL from Laravel database config.
     *
     * @return string
     */
    protected function getConnectionUri()
    {
        $name = config('websockets.replication.redis.connection') ?: 'default';
        $config = config('database.redis')[$name];

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
     * Get the Pub/Sub Topic name to subscribe based on the
     * app ID and channel name.
     *
     * @param  mixed  $appId
     * @param  string|null  $channel
     * @return string
     */
    protected function getTopicName($appId, string $channel = null): string
    {
        $prefix = config('database.redis.options.prefix', null);

        $hash = "{$prefix}{$appId}";

        if ($channel) {
            $hash .= ":{$channel}";
        }

        return $hash;
    }
}
