<?php

namespace BeyondCode\LaravelWebSockets\Tests;

use BeyondCode\LaravelWebSockets\ClientProviders\Client;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Exceptions\InvalidSignature;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Exceptions\UnknownAppKey;
use BeyondCode\LaravelWebSockets\LaravelEcho\WebSocket\PusherServer;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Message;

class ConnectionTest extends TestCase
{
    /** @var \BeyondCode\LaravelWebSockets\LaravelEcho\WebSocket\PusherServer */
    protected $pusherServer;

    public function setUp()
    {
        parent::setUp();

        $this->pusherServer = app(PusherServer::class);
    }

    /** @test */
    public function unknown_app_keys_can_not_connect()
    {
        $this->expectException(UnknownAppKey::class);

        $this->pusherServer->onOpen($this->getWebSocketConnection('/?appKey=test'));
    }

    /** @test */
    public function known_app_keys_can_connect()
    {
        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        $connection->assertSentEvent('pusher:connection_established');
    }

    /** @test */
    public function successful_connections_have_the_client_attached()
    {
        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        $this->assertInstanceOf(Client::class, $connection->client);
        $this->assertSame(1234, $connection->client->appId);
        $this->assertSame('TestKey', $connection->client->appKey);
        $this->assertSame('TestSecret', $connection->client->appSecret);
        $this->assertSame('Test Client', $connection->client->name);
    }

    /** @test */
    public function ping_returns_pong()
    {
        $connection = $this->getWebSocketConnection();

        $message = new Message('{"event": "pusher:ping"}');

        $this->pusherServer->onOpen($connection);

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher:pong');
    }

    /** @test */
    public function clients_can_subscribe_to_basic_channels()
    {
        $connection = $this->getWebSocketConnection();

        $message = new Message(json_encode([
            'event' => 'pusher:subscribe',
            'data' => [
                'channel' => 'basic-channel'
            ],
        ]));

        $this->pusherServer->onOpen($connection);

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'basic-channel'
        ]);
    }

    /** @test */
    public function clients_need_valid_auth_signatures_for_private_channels()
    {
        $this->expectException(InvalidSignature::class);

        $connection = $this->getWebSocketConnection();

        $message = new Message(json_encode([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => 'invalid',
                'channel' => 'private-channel'
            ],
        ]));

        $this->pusherServer->onOpen($connection);

        $this->pusherServer->onMessage($connection, $message);
    }

    /** @test */
    public function clients_can_subscribe_to_private_channels()
    {
        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        $signature = "{$connection->socketId}:private-channel";

        $message = new Message(json_encode([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => $connection->client->appKey.':'.hash_hmac('sha256', $signature, $connection->client->appSecret),
                'channel' => 'private-channel'
            ],
        ]));

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'private-channel'
        ]);
    }

    /** @test */
    public function clients_need_valid_auth_signatures_for_presence_channels()
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
    public function clients_can_subscribe_to_presence_channels()
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