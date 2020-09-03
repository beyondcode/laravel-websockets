<?php

namespace BeyondCode\LaravelWebSockets\Tests\PubSub;

use BeyondCode\LaravelWebSockets\PubSub\Drivers\RedisClient;
use BeyondCode\LaravelWebSockets\Tests\Mocks\RedisFactory;
use BeyondCode\LaravelWebSockets\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use React\EventLoop\Factory as LoopFactory;

class RedisDriverTest extends TestCase
{
    /**
     * The Redis manager instance.
     *
     * @var \Illuminate\Redis\RedisManager
     */
    protected $redis;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->runOnlyOnRedisReplication();

        $this->redis = Cache::getRedis();

        $this->redis->hdel('laravel_database_1234', 'connections');
    }

    /** @test */
    public function redis_listener_responds_properly_on_payload()
    {
        $connection = $this->getConnectedWebSocketConnection(['test-channel']);

        $this->pusherServer->onOpen($connection);

        $channelData = [
            'user_id' => 1,
            'user_info' => [
                'name' => 'Marcel',
            ],
        ];

        $payload = json_encode([
            'appId' => '1234',
            'event' => 'test',
            'data' => $channelData,
            'socketId' => $connection->socketId,
        ]);

        $this->getSubscribeClient()->onMessage('1234:test-channel', $payload);

        $this->getSubscribeClient()
            ->assertEventDispatched('message')
            ->assertCalledWithArgs('subscribe', ['laravel_database_1234:test-channel'])
            ->assertCalledWithArgs('onMessage', [
                '1234:test-channel', $payload,
            ]);
    }

    /** @test */
    public function redis_listener_responds_properly_on_payload_by_direct_call()
    {
        $connection = $this->getConnectedWebSocketConnection(['test-channel']);

        $this->pusherServer->onOpen($connection);

        $channelData = [
            'user_id' => 1,
            'user_info' => [
                'name' => 'Marcel',
            ],
        ];

        $payload = json_encode([
            'appId' => '1234',
            'event' => 'test',
            'data' => $channelData,
            'socketId' => $connection->socketId,
        ]);

        $client = (new RedisClient)->boot(
            LoopFactory::create(), RedisFactory::class
        );

        $client->onMessage('1234:test-channel', $payload);

        $client->getSubscribeClient()
            ->assertEventDispatched('message');
    }

    /** @test */
    public function redis_tracks_app_connections_count()
    {
        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        $this->getSubscribeClient()
            ->assertCalledWithArgs('subscribe', ['laravel_database_1234']);

        $this->getPublishClient()
            ->assertNothingCalled();

        $this->assertEquals(1, $this->redis->hget('laravel_database_1234', 'connections'));
    }

    /** @test */
    public function redis_tracks_app_connections_count_on_disconnect()
    {
        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        $this->getSubscribeClient()
            ->assertCalledWithArgs('subscribe', ['laravel_database_1234'])
            ->assertNotCalledWithArgs('unsubscribe', ['laravel_database_1234']);

        $this->getPublishClient()
            ->assertNothingCalled();

        $this->assertEquals(1, $this->redis->hget('laravel_database_1234', 'connections'));

        $this->pusherServer->onClose($connection);

        $this->getPublishClient()
            ->assertNothingCalled();

        $this->assertEquals(0, $this->redis->hget('laravel_database_1234', 'connections'));
    }
}
