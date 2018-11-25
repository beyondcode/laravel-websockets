<?php

namespace BeyondCode\LaravelWebSockets\Tests;

use BeyondCode\LaravelWebSockets\ClientProviders\Client;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Exceptions\InvalidSignatureException;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Exceptions\UnknownAppKeyException;
use BeyondCode\LaravelWebSockets\LaravelEcho\WebSocket\PusherServer;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Message;
use Ratchet\RFC6455\Messaging\MessageInterface;

class ConnectionTest extends TestCase
{
    /** @test */
    public function unknown_app_keys_can_not_connect()
    {
        $this->expectException(UnknownAppKeyException::class);

        /** @var PusherServer $server */
        $server = app(PusherServer::class);

        $server->onOpen($this->getWebSocketConnection('/?appKey=test'));
    }

    /** @test */
    public function known_app_keys_can_connect()
    {
        /** @var PusherServer $server */
        $server = app(PusherServer::class);

        $connection = $this->getWebSocketConnection();

        $server->onOpen($connection);

        $connection->assertSentEvent('pusher:connection_established');
    }

    /** @test */
    public function successful_connections_have_the_client_attached()
    {
        /** @var PusherServer $server */
        $server = app(PusherServer::class);

        $connection = $this->getWebSocketConnection();

        $server->onOpen($connection);

        $this->assertInstanceOf(Client::class, $connection->client);
        $this->assertSame(1234, $connection->client->appId);
        $this->assertSame('TestKey', $connection->client->appKey);
        $this->assertSame('TestSecret', $connection->client->appSecret);
        $this->assertSame('Test Client', $connection->client->name);
    }

    /** @test */
    public function ping_returns_pong()
    {
        /** @var PusherServer $server */
        $server = app(PusherServer::class);

        $connection = $this->getWebSocketConnection();

        $message = new Message('{"event": "pusher:ping"}');

        $server->onOpen($connection);

        $server->onMessage($connection, $message);

        $connection->assertSentEvent('pusher:pong');
    }

    /** @test */
    public function clients_can_subscribe_to_basic_channels()
    {
        /** @var PusherServer $server */
        $server = app(PusherServer::class);

        $connection = $this->getWebSocketConnection();

        $message = new Message(json_encode([
            'event' => 'pusher:subscribe',
            'data' => [
                'channel' => 'basic-channel'
            ],
        ]));

        $server->onOpen($connection);

        $server->onMessage($connection, $message);

        $connection->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'basic-channel'
        ]);
    }

    /** @test */
    public function clients_need_valid_auth_signatures_for_private_channels()
    {
        $this->expectException(InvalidSignatureException::class);

        /** @var PusherServer $server */
        $server = app(PusherServer::class);

        $connection = $this->getWebSocketConnection();

        $message = new Message(json_encode([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => 'invalid',
                'channel' => 'private-channel'
            ],
        ]));

        $server->onOpen($connection);

        $server->onMessage($connection, $message);
    }

    /** @test */
    public function clients_can_subscribe_to_private_channels()
    {
        /** @var PusherServer $server */
        $server = app(PusherServer::class);

        $connection = $this->getWebSocketConnection();

        $server->onOpen($connection);

        $signature = "{$connection->socketId}:private-channel";

        $message = new Message(json_encode([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => $connection->client->appKey.':'.hash_hmac('sha256', $signature, $connection->client->appSecret),
                'channel' => 'private-channel'
            ],
        ]));

        $server->onMessage($connection, $message);

        $connection->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'private-channel'
        ]);
    }
}