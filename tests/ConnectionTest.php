<?php

namespace BeyondCode\LaravelWebSockets\Tests;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Message;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\ConnectionsOverCapacity;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\OriginNotAllowed;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\UnknownAppKey;
use Illuminate\Support\Facades\Redis;

class ConnectionTest extends TestCase
{
    /** @test */
    public function unknown_app_keys_can_not_connect()
    {
        $this->expectException(UnknownAppKey::class);

        $this->pusherServer->onOpen($this->getWebSocketConnection('test'));
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
        $this->runOnlyOnLocalReplication();

        $this->app['config']->set('websockets.apps.0.capacity', 2);

        $this->getConnectedWebSocketConnection(['test-channel']);
        $this->getConnectedWebSocketConnection(['test-channel']);
        $this->expectException(ConnectionsOverCapacity::class);
        $this->getConnectedWebSocketConnection(['test-channel']);
    }

    /** @test */
    public function app_can_not_exceed_maximum_capacity_on_redis_replication()
    {
        $this->runOnlyOnRedisReplication();

        Redis::hdel('laravel_database_1234', 'connections');

        $this->app['config']->set('websockets.apps.0.capacity', 2);

        $this->getConnectedWebSocketConnection(['test-channel']);
        $this->getConnectedWebSocketConnection(['test-channel']);

        $this->getPublishClient()
            ->assertCalledWithArgsCount(2, 'hincrby', ['laravel_database_1234', 'connections', 1]);

        $this->expectException(ConnectionsOverCapacity::class);

        $this->getConnectedWebSocketConnection(['test-channel']);
    }

    /** @test */
    public function successful_connections_have_the_app_attached()
    {
        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        $this->assertInstanceOf(App::class, $connection->app);
        $this->assertSame('1234', $connection->app->id);
        $this->assertSame('TestKey', $connection->app->key);
        $this->assertSame('TestSecret', $connection->app->secret);
        $this->assertSame('Test App', $connection->app->name);
    }

    /** @test */
    public function ping_returns_pong()
    {
        $connection = $this->getWebSocketConnection();

        $message = new Message(['event' => 'pusher:ping']);

        $this->pusherServer->onOpen($connection);

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher:pong');
    }

    /** @test */
    public function origin_validation_should_fail_for_no_origin()
    {
        $this->expectException(OriginNotAllowed::class);

        $connection = $this->getWebSocketConnection('TestOrigin');

        $this->pusherServer->onOpen($connection);

        $connection->assertSentEvent('pusher:connection_established');
    }

    /** @test */
    public function origin_validation_should_fail_for_wrong_origin()
    {
        $this->expectException(OriginNotAllowed::class);

        $connection = $this->getWebSocketConnection('TestOrigin', ['Origin' => 'https://google.ro']);

        $this->pusherServer->onOpen($connection);

        $connection->assertSentEvent('pusher:connection_established');
    }

    /** @test */
    public function origin_validation_should_pass_for_the_right_origin()
    {
        $connection = $this->getWebSocketConnection('TestOrigin', ['Origin' => 'https://test.origin.com']);

        $this->pusherServer->onOpen($connection);

        $connection->assertSentEvent('pusher:connection_established');
    }
}
