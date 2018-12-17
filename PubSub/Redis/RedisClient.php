<?php

namespace BeyondCode\LaravelWebSockets\PubSub\Redis;

use BeyondCode\LaravelWebSockets\PubSub\PubSubInterface;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Clue\React\Block;
use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use Illuminate\Support\Str;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

class RedisClient implements PubSubInterface
{

    const REDIS_KEY = ':websockets:replication:';
    protected $apps;
    protected $loop;
    protected $serverId;
    protected $publishClient;
    protected $subscribeClient;

    public function __construct()
    {
        $this->apps     = collect(config('websockets.apps'));
        $this->serverId = Str::uuid()->toString();
    }

    public function publish(string $appId, array $payload): bool
    {
        $payload['appId']    = $appId;
        $payload['serverId'] = $this->serverId;
        $this->publishClient->publish(self::REDIS_KEY, json_encode($payload));
        return true;
    }

    public function subscribe(LoopInterface $loop): PubSubInterface
    {
        $this->loop = $loop;
        [$this->publishClient, $this->subscribeClient] = Block\awaitAll([$this->publishConnection(), $this->subscribeConnection()], $this->loop);
        return $this->publishClient;
    }

    protected function publishConnection(): PromiseInterface
    {
        $connectionUri = $this->getConnectionUri();
        $factory       = new Factory($this->loop);
        return $factory->createClient($connectionUri)->then(
            function (Client $client) {
                $this->publishClient = $client;
                return $this;
            }
        );
    }


    protected function subscribeConnection(): PromiseInterface
    {
        $connectionUri = $this->getConnectionUri();
        $factory       = new Factory($this->loop);
        return $factory->createClient($connectionUri)->then(
            function (Client $client) {
                $this->subscribeClient = $client;
                $this->onConnected();
                return $this;
            }
        );
    }

    protected function getConnectionUri()
    {
        $name   = config('websockets.replication.connection') ?? 'default';
        $config = config('database.redis.' . $name);
        $host   = $config['host'];
        $port   = $config['port'] ? (':' . $config['port']) : ':6379';

        $query = [];
        if ($config['password']) {
            $query['password'] = $config['password'];
        }
        if ($config['database']) {
            $query['database'] = $config['database'];
        }
        $query = http_build_query($query);

        return "redis://$host$port" . ($query ? '?' . $query : '');
    }

    protected function onConnected()
    {
        $this->subscribeClient->subscribe(self::REDIS_KEY);
        $this->subscribeClient->on('message', function ($channel, $payload) {
            $this->onMessage($channel, $payload);
        });
    }

    protected function onMessage($channel, $payload)
    {
        $payload = json_decode($payload);

        if ($this->serverId === $payload->serverId) {
            return false;
        }

        /* @var $channelManager ChannelManager */
        $channelManager = app(ChannelManager::class);
        $channelSearch        = $channelManager->find($payload->appId, $payload->channel);

        if ($channelSearch === null) {
            return false;
        }

        $channel->broadcast($payload);
        return true;
    }

}