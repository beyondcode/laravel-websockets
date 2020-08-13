<?php

namespace BeyondCode\LaravelWebSockets\PubSub\Drivers;

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
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var string
     */
    protected $serverId;

    /**
     * @var Client
     */
    protected $publishClient;

    /**
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
     * RedisClient constructor.
     */
    public function __construct()
    {
        $this->serverId = Str::uuid()->toString();
    }

    /**
     * Boot the RedisClient, initializing the connections.
     *
     * @param LoopInterface $loop
     * @return ReplicationInterface
     */
    public function boot(LoopInterface $loop): ReplicationInterface
    {
        $this->loop = $loop;

        $connectionUri = $this->getConnectionUri();
        $factory = new Factory($this->loop);

        $this->publishClient = $factory->createLazyClient($connectionUri);
        $this->subscribeClient = $factory->createLazyClient($connectionUri);

        $this->subscribeClient->on('message', function ($channel, $payload) {
            $this->onMessage($channel, $payload);
        });

        return $this;
    }

    /**
     * Handle a message received from Redis on a specific channel.
     *
     * @param string $redisChannel
     * @param string $payload
     */
    protected function onMessage(string $redisChannel, string $payload)
    {
        $payload = json_decode($payload);

        // Ignore messages sent by ourselves
        if (isset($payload->serverId) && $this->serverId === $payload->serverId) {
            return;
        }

        // Pull out the app ID. See RedisPusherBroadcaster
        $appId = $payload->appId;

        // We need to put the channel name in the payload.
        // We strip the app ID from the channel name, websocket clients
        // expect the channel name to not include the app ID.
        $payload->channel = Str::after($redisChannel, "$appId:");

        /* @var ChannelManager $channelManager */
        $channelManager = app(ChannelManager::class);

        // Load the Channel instance, if any
        $channel = $channelManager->find($appId, $payload->channel);

        // If no channel is found, none of our connections want to
        // receive this message, so we ignore it.
        if (! $channel) {
            return;
        }

        $socket = $payload->socket ?? null;

        // Remove fields intended for internal use from the payload
        unset($payload->socket);
        unset($payload->serverId);
        unset($payload->appId);

        // Push the message out to connected websocket clients
        $channel->broadcastToEveryoneExcept($payload, $socket, $appId, false);
    }

    /**
     * Subscribe to a channel on behalf of websocket user.
     *
     * @param string $appId
     * @param string $channel
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

        return true;
    }

    /**
     * Unsubscribe from a channel on behalf of a websocket user.
     *
     * @param string $appId
     * @param string $channel
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

        return true;
    }

    /**
     * Publish a message to a channel on behalf of a websocket user.
     *
     * @param string $appId
     * @param string $channel
     * @param stdClass $payload
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
     * @param string $appId
     * @param string $channel
     * @param string $socketId
     * @param string $data
     */
    public function joinChannel(string $appId, string $channel, string $socketId, string $data)
    {
        $this->publishClient->__call('hset', ["$appId:$channel", $socketId, $data]);
    }

    /**
     * Remove a member from the channel. To be called when they have
     * unsubscribed from the channel.
     *
     * @param string $appId
     * @param string $channel
     * @param string $socketId
     */
    public function leaveChannel(string $appId, string $channel, string $socketId)
    {
        $this->publishClient->__call('hdel', ["$appId:$channel", $socketId]);
    }

    /**
     * Retrieve the full information about the members in a presence channel.
     *
     * @param string $appId
     * @param string $channel
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
     * @param string $appId
     * @param array $channelNames
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
        $name = config('websockets.replication.connection') ?? 'default';
        $config = config("database.redis.$name");
        $host = $config['host'];
        $port = $config['port'] ? (':'.$config['port']) : ':6379';

        $query = [];
        if ($config['password']) {
            $query['password'] = $config['password'];
        }
        if ($config['database']) {
            $query['database'] = $config['database'];
        }
        $query = http_build_query($query);

        return "redis://$host$port".($query ? '?'.$query : '');
    }
}
