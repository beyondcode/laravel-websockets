<?php

namespace BeyondCode\LaravelWebSockets\PubSub\Redis;

use stdClass;
use Illuminate\Support\Str;
use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use React\EventLoop\LoopInterface;
use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;

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
     * Boot the RedisClient, initializing the connections
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
     * Handle a message received from Redis on a specific channel
     *
     * @param string $redisChannel
     * @param string $payload
     * @return bool
     */
    protected function onMessage(string $redisChannel, string $payload)
    {
        $payload = json_decode($payload);

        // Ignore messages sent by ourselves
        if (isset($payload->serverId) && $this->serverId === $payload->serverId) {
            return false;
        }

        // We need to put the channel name in the payload
        $payload->channel = $redisChannel;

        /* @var $channelManager ChannelManager */
        $channelManager = app(ChannelManager::class);

        // Load the Channel instance, if any
        $channel = $channelManager->find($payload->appId, $payload->channel);
        if ($channel === null) {
            return false;
        }

        $socket = $payload->socket;

        // Remove the internal keys from the payload
        unset($payload->socket);
        unset($payload->serverId);
        unset($payload->appId);

        // Push the message out to connected websocket clients
        $channel->broadcastToEveryoneExcept($payload, $socket);

        return true;
    }

    /**
     * Subscribe to a channel on behalf of websocket user
     *
     * @param string $appId
     * @param string $channel
     * @return bool
     */
    public function subscribe(string $appId, string $channel): bool
    {
        if (! isset($this->subscribedChannels[$channel])) {
            // We're not subscribed to the channel yet, subscribe and set the count to 1
            $this->subscribeClient->__call('subscribe', [$channel]);
            $this->subscribedChannels[$channel] = 1;
        } else {
            // Increment the subscribe count if we've already subscribed
            $this->subscribedChannels[$channel]++;
        }

        return true;
    }

    /**
     * Unsubscribe from a channel on behalf of a websocket user
     *
     * @param string $appId
     * @param string $channel
     * @return bool
     */
    public function unsubscribe(string $appId, string $channel): bool
    {
        if (! isset($this->subscribedChannels[$channel])) {
            return false;
        }

        // Decrement the subscription count for this channel
        $this->subscribedChannels[$channel]--;

        // If we no longer have subscriptions to that channel, unsubscribe
        if ($this->subscribedChannels[$channel] < 1) {
            $this->subscribeClient->__call('unsubscribe', [$channel]);
            unset($this->subscribedChannels[$channel]);
        }

        return true;
    }

    /**
     * Publish a message to a channel on behalf of a websocket user
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

        $this->publishClient->__call('publish', [$channel, json_encode($payload)]);

        return true;
    }

    /**
     * Build the Redis connection URL from Laravel database config
     *
     * @return string
     */
    protected function getConnectionUri()
    {
        $name = config('websockets.replication.connection') ?? 'default';
        $config = config("database.redis.$name");
        $host = $config['host'];
        $port = $config['port'] ? (':' . $config['port']) : ':6379';

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
