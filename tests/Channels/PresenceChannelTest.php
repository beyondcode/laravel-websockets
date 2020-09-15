<?php

namespace BeyondCode\LaravelWebSockets\Tests\Channels;

use BeyondCode\LaravelWebSockets\Tests\Mocks\Connection;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Message;
use BeyondCode\LaravelWebSockets\Tests\TestCase;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\InvalidSignature;

class PresenceChannelTest extends TestCase
{
    /** @test */
    public function clients_need_valid_auth_signatures_to_join_presence_channels()
    {
        $this->expectException(InvalidSignature::class);

        $connection = $this->getWebSocketConnection();

        $message = new Message(json_encode([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => 'invalid',
                'channel' => 'presence-channel',
            ],
        ]));

        $this->pusherServer->onOpen($connection);

        $this->pusherServer->onMessage($connection, $message);
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

        $message = $this->getSignedMessage($connection, 'presence-channel', $channelData);

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'presence-channel',
        ]);
    }

    /** @test */
    public function clients_with_no_user_info_can_join_presence_channels()
    {
        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        $channelData = [
            'user_id' => 1,
        ];

        $message = $this->getSignedMessage($connection, 'presence-channel', $channelData);

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'presence-channel',
        ]);
    }

    /** @test */
    public function multiple_clients_with_same_user_id_are_counted_once()
    {
        $this->pusherServer->onOpen($connection = $this->getWebSocketConnection());
        $this->pusherServer->onOpen($connection2 = $this->getWebSocketConnection());

        $channelName = 'presence-channel';
        $channelData = [
            'user_id' => $userId = 1,
        ];

        $this->pusherServer->onMessage($connection, $this->getSignedMessage($connection, $channelName, $channelData));
        $this->pusherServer->onMessage($connection2, $this->getSignedMessage($connection2, $channelName, $channelData));

        $connection2->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => $channelName,
            'data' => json_encode([
                'presence' => [
                    'ids' => [(string)$userId],
                    'hash' => [
                        (string)$userId => [],
                    ],
                    'count' => 1,
                ],
            ]),
        ]);
    }

    private function getSignedMessage(Connection $connection, string $channelName, array $channelData): Message
    {
        $signature = "{$connection->socketId}:{$channelName}:" . json_encode($channelData);

        return new Message(json_encode([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => $connection->app->key . ':' . hash_hmac('sha256', $signature, $connection->app->secret),
                'channel' => $channelName,
                'channel_data' => json_encode($channelData),
            ],
        ]));
    }
}
