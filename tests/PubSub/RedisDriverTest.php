<?php

namespace BeyondCode\LaravelWebSockets\Tests\PubSub;

use BeyondCode\LaravelWebSockets\Tests\TestCase;

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
}
