<?php

namespace BeyondCode\LaravelWebSockets\Tests;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Message;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\ConnectionsOverCapacity;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\UnknownAppKey;

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
    public function app_can_not_exceed_maximum_capacity()
    {
        $this->app['config']->set('websockets.apps.0.capacity', 2);

        $this->getConnectedWebSocketConnection(['test-channel']);
        $this->getConnectedWebSocketConnection(['test-channel']);
        $this->expectException(ConnectionsOverCapacity::class);
        $this->getConnectedWebSocketConnection(['test-channel']);
    }

    /** @test */
    public function successful_connections_have_the_app_attached()
    {
        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        $this->assertInstanceOf(App::class, $connection->app);
        $this->assertSame(1234, $connection->app->id);
        $this->assertSame('TestKey', $connection->app->key);
        $this->assertSame('TestSecret', $connection->app->secret);
        $this->assertSame('Test App', $connection->app->name);
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
