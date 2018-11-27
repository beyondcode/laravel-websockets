<?php

namespace BeyondCode\LaravelWebsockets\Tests\Channels;

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
                'channel' => 'presence-channel'
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
                'name' => 'Marcel'
            ]
        ];

        $signature = "{$connection->socketId}:presence-channel:".json_encode($channelData);

        $message = new Message(json_encode([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => $connection->client->appKey.':'.hash_hmac('sha256', $signature, $connection->client->appSecret),
                'channel' => 'presence-channel',
                'channel_data' => json_encode($channelData)
            ],
        ]));

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'presence-channel',
        ]);
    }
}