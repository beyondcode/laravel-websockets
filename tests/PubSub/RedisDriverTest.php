<?php

namespace BeyondCode\LaravelWebSockets\Tests\PubSub;

use BeyondCode\LaravelWebSockets\PubSub\Drivers\RedisClient;
use BeyondCode\LaravelWebSockets\Tests\TestCase;
use BeyondCode\LaravelWebSockets\Tests\Mocks\RedisFactory;
use React\EventLoop\Factory as LoopFactory;

class RedisDriverTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->runOnlyOnRedisReplication();
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
            'socket' => $connection->socketId,
        ]);

        $this->getSubscribeClient()->onMessage('1234:test-channel', $payload);

        $this->getSubscribeClient()
            ->assertEventDispatched('message')
            ->assertCalledWithArgs('subscribe', ['1234:test-channel'])
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
            'socket' => $connection->socketId,
        ]);

        $client = (new RedisClient)->boot(
            LoopFactory::create(), RedisFactory::class
        );

        $client->onMessage('1234:test-channel', $payload);

        $client->getSubscribeClient()
            ->assertEventDispatched('message');
    }
}
