<?php

namespace BeyondCode\LaravelWebSockets\Tests\Channels;

use BeyondCode\LaravelWebSockets\Tests\Mocks\Message;
use BeyondCode\LaravelWebSockets\Tests\TestCase;

class PresenceChannelReplicationTest extends TestCase
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
    public function clients_with_valid_auth_signatures_can_join_presence_channels()
    {
        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        $channelData = [
            'user_id' => 1,
            'user_info' => [
                'name' => 'Marcel',
            ],
        ];

        $signature = "{$connection->socketId}:presence-channel:".json_encode($channelData);

        $message = new Message([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => $connection->app->key.':'.hash_hmac('sha256', $signature, $connection->app->secret),
                'channel' => 'presence-channel',
                'channel_data' => json_encode($channelData),
            ],
        ]);

        $this->pusherServer->onMessage($connection, $message);

        $this->getPublishClient()
            ->assertCalledWithArgs('hset', [
                'laravel_database_1234:presence-channel',
                $connection->socketId,
                json_encode($channelData),
            ])
            ->assertCalledWithArgs('hgetall', ['laravel_database_1234:presence-channel'])
            ->assertCalled('publish');

        $this->assertNotNull(
            $this->redis->hget('laravel_database_1234:presence-channel', $connection->socketId)
        );
    }

    /** @test */
    public function clients_with_valid_auth_signatures_can_leave_presence_channels()
    {
        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        $channelData = [
            'user_id' => 1,
        ];

        $signature = "{$connection->socketId}:presence-channel:".json_encode($channelData);

        $message = new Message([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => $connection->app->key.':'.hash_hmac('sha256', $signature, $connection->app->secret),
                'channel' => 'presence-channel',
                'channel_data' => json_encode($channelData),
            ],
        ]);

        $this->pusherServer->onMessage($connection, $message);

        $this->getSubscribeClient()
            ->assertEventDispatched('message');

        $this->getPublishClient()
            ->assertCalled('hset')
            ->assertCalledWithArgs('hgetall', ['laravel_database_1234:presence-channel'])
            ->assertCalled('publish');

        $this->getPublishClient()
            ->resetAssertions();

        $message = new Message([
            'event' => 'pusher:unsubscribe',
            'data' => [
                'auth' => $connection->app->key.':'.hash_hmac('sha256', $signature, $connection->app->secret),
                'channel' => 'presence-channel',
            ],
        ]);

        $this->pusherServer->onMessage($connection, $message);

        $this->getPublishClient()
            ->assertCalled('hdel')
            ->assertCalled('publish');
    }

    /** @test */
    public function clients_with_no_user_info_can_join_presence_channels()
    {
        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        $channelData = [
            'user_id' => 1,
        ];

        $signature = "{$connection->socketId}:presence-channel:".json_encode($channelData);

        $message = new Message([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => $connection->app->key.':'.hash_hmac('sha256', $signature, $connection->app->secret),
                'channel' => 'presence-channel',
                'channel_data' => json_encode($channelData),
            ],
        ]);

        $this->pusherServer->onMessage($connection, $message);

        $this->getPublishClient()
            ->assertCalled('hset')
            ->assertCalledWithArgs('hgetall', ['laravel_database_1234:presence-channel'])
            ->assertCalled('publish');
    }
}
