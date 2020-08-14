<?php

namespace BeyondCode\LaravelWebSockets\PubSub\Drivers;

use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use Illuminate\Support\Str;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use stdClass;

class RedisClient implements ReplicationInterface
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
     * Handle a message received from Redis on a specific channel.
     *
     * @param  string  $redisChannel
     * @param  string  $payload
     * @return void
     */
    protected function onMessage(string $redisChannel, string $payload)
    {
        $payload = json_decode($payload);

        // Ignore messages sent by ourselves.
        if (isset($payload->serverId) && $this->serverId === $payload->serverId) {
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

        $socket = $payload->socket ?? null;

        // Remove fields intended for internal use from the payload.
        unset($payload->socket);
        unset($payload->serverId);
        unset($payload->appId);

        // Push the message out to connected websocket clients.
        $channel->broadcastToEveryoneExcept($payload, $socket, $appId, false);
    }

    /**
     * Subscribe to a channel on behalf of websocket user.
     *
     * @param  string  $appId
     * @param  string  $channel
     * @return bool
     */
    public function subscribe(string $appId, string $channel): bool
    {
        if (! isset($this->subscribedChannels["$appId:$channel"])) {
            // We're not subscribed to the channel yet, subscribe and set the count to 1
            $this->subscribeClient->__call('subscribe', ["$appId:$channel"]);
            $this->subscribedChannels["$appId:$channel"] = 1;
        } else {
            // Increment the subscribe count if we've already subscribed
            $this->subscribedChannels["$appId:$channel"]++;
        }

        DashboardLogger::replicatorSubscribed($appId, $channel, $this->serverId);

        return true;
    }

    /**
     * Unsubscribe from a channel on behalf of a websocket user.
     *
     * @param  string  $appId
     * @param  string  $channel
     * @return bool
     */
    public function unsubscribe(string $appId, string $channel): bool
    {
        if (! isset($this->subscribedChannels["$appId:$channel"])) {
            return false;
        }

        // Decrement the subscription count for this channel
        $this->subscribedChannels["$appId:$channel"]--;

        // If we no longer have subscriptions to that channel, unsubscribe
        if ($this->subscribedChannels["$appId:$channel"] < 1) {
            $this->subscribeClient->__call('unsubscribe', ["$appId:$channel"]);

            unset($this->subscribedChannels["$appId:$channel"]);
        }

        DashboardLogger::replicatorUnsubscribed($appId, $channel, $this->serverId);

        return true;
    }

    /**
     * Publish a message to a channel on behalf of a websocket user.
     *
     * @param  string  $appId
     * @param  string  $channel
     * @param  stdClass  $payload
     * @return bool
     */
    public function publish(string $appId, string $channel, stdClass $payload): bool
    {
        $payload->appId = $appId;
        $payload->serverId = $this->serverId;

        $this->publishClient->__call('publish', ["$appId:$channel", json_encode($payload)]);

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
    public function joinChannel(string $appId, string $channel, string $socketId, string $data)
    {
        $this->publishClient->__call('hset', ["$appId:$channel", $socketId, $data]);
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
    public function leaveChannel(string $appId, string $channel, string $socketId)
    {
        $this->publishClient->__call('hdel', ["$appId:$channel", $socketId]);
    }

    /**
     * Retrieve the full information about the members in a presence channel.
     *
     * @param  string  $appId
     * @param  string  $channel
     * @return PromiseInterface
     */
    public function channelMembers(string $appId, string $channel): PromiseInterface
    {
        return $this->publishClient->__call('hgetall', ["$appId:$channel"])
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
    public function channelMemberCounts(string $appId, array $channelNames): PromiseInterface
    {
        $this->publishClient->__call('multi', []);

        foreach ($channelNames as $channel) {
            $this->publishClient->__call('hlen', ["$appId:$channel"]);
        }

        return $this->publishClient->__call('exec', [])
            ->then(function ($data) use ($channelNames) {
                return array_combine($channelNames, $data);
            });
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
}
