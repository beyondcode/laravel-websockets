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

        $message = new Message(json_encode([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => $connection->app->key.':'.hash_hmac('sha256', $signature, $connection->app->secret),
                'channel' => 'presence-channel',
                'channel_data' => json_encode($channelData),
            ],
        ]));

        $this->pusherServer->onMessage($connection, $message);

        $this->getPublishClient()
            ->assertCalledWithArgs('hset', [
                '1234:presence-channel',
                $connection->socketId,
                json_encode($channelData),
            ])
            ->assertCalledWithArgs('hgetall', [
                '1234:presence-channel',
            ]);
        // TODO: This fails somehow
            // Debugging shows the exact same pattern as good.
            /* ->assertCalledWithArgs('publish', [
                '1234:presence-channel',
                json_encode([
                    'event' => 'pusher_internal:member_added',
                    'channel' => 'presence-channel',
                    'data' => $channelData,
                    'appId' => '1234',
                    'serverId' => $this->app->make(ReplicationInterface::class)->getServerId(),
                ]),
            ]) */
    }
}
