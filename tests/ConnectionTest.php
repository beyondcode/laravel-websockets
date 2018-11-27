<?php

namespace BeyondCode\LaravelWebSockets\Tests;

use BeyondCode\LaravelWebSockets\ClientProviders\Client;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\UnknownAppKey;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Message;

class ConnectionTest extends TestCase
{

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
}