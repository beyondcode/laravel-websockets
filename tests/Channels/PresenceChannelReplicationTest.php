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
            ->hgetall($this->replicator->getTopicName('1234', 'presence-channel'))
            ->then(function ($joinedUsers) use ($connection, $channelData) {
                $this->assertEquals([
                    $connection->socketId,
                    json_encode($channelData),
                ], $joinedUsers);
            });
    }

    /** @test */
    public function clients_with_valid_auth_signatures_can_leave_presence_channels()
    {
        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        // Initiate a second connection that is going to receive the presence channel updates.
        $anotherConnection = $this->joinPresenceChannel('presence-channel', $anotherChannelData = [
            'user_id' => 2,
        ]);

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

        // Both of the connections should be stored into Redis.
        $this->getPublishClient()
            ->hgetall($this->replicator->getTopicName('1234', 'presence-channel'))
            ->then(function ($joinedUsers) use ($connection, $anotherConnection, $channelData, $anotherChannelData) {
                $this->assertEquals([
                    $anotherConnection->socketId,
                    json_encode($anotherChannelData),
                    $connection->socketId,
                    json_encode($channelData),
                ], $joinedUsers);
            });

        // The already-connected member should receive member_added.
        $anotherConnection->assertSentEvent('pusher_internal:member_added', [
            'data' => json_encode(['user_id' => 1]),
            'channel' => 'presence-channel',
        ]);

        $message = new Message([
            'event' => 'pusher:unsubscribe',
            'data' => [
                'auth' => $connection->app->key.':'.hash_hmac('sha256', $signature, $connection->app->secret),
                'channel' => 'presence-channel',
            ],
        ]);

        $this->pusherServer->onMessage($connection, $message);

        // In the list of the existing members, only one user should remain active.
        $this->getPublishClient()
            ->hgetall($this->replicator->getTopicName('1234', 'presence-channel'))
            ->then(function ($joinedUsers) use ($anotherConnection, $anotherChannelData) {
                $this->assertEquals([
                    $anotherConnection->socketId,
                    json_encode($anotherChannelData),
                ], $joinedUsers);
            });

        // If the user leaves, the existing members should get member_removed
        $anotherConnection->assertSentEvent('pusher_internal:member_removed', [
            'data' => json_encode(['user_id' => 1]),
            'channel' => 'presence-channel',
        ]);
    }
}
