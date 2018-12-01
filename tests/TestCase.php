<?php

namespace BeyondCode\LaravelWebSockets\Tests;

use BeyondCode\LaravelWebSockets\Tests\Mocks\Message;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\WebSocketHandler;
use GuzzleHttp\Psr7\Request;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Connection;
use BeyondCode\LaravelWebSockets\WebSocketsServiceProvider;
use Ratchet\ConnectionInterface;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /** @var \BeyondCode\LaravelWebSockets\WebSockets\WebSocketHandler */
    protected $pusherServer;

    /** @var \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager */
    protected $channelManager;

    public function setUp()
    {
        parent::setUp();

        $this->pusherServer = app(WebSocketHandler::class);

        $this->channelManager = app(ChannelManager::class);
    }

    protected function getPackageProviders($app)
    {
        return [WebSocketsServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('websockets.apps', [
            [
                'name' => 'Test App',
                'id' => 1234,
                'key' => 'TestKey',
                'secret' => 'TestSecret',
                'enable_client_messages' => false,
            ],
        ]);
    }

    protected function getWebSocketConnection(string $url = '/?appKey=TestKey'): Connection
    {
        $connection = new Connection();

        $connection->httpRequest = new Request('GET', $url);

        return $connection;
    }

    protected function getConnectedWebSocketConnection(array $channelsToJoin = [], string $url = '/?appKey=TestKey'): Connection
    {
        $connection = new Connection();

        $connection->httpRequest = new Request('GET', $url);

        $this->pusherServer->onOpen($connection);

        foreach ($channelsToJoin as $channel) {
            $message = new Message(json_encode([
                'event' => 'pusher:subscribe',
                'data' => [
                    'channel' => $channel
                ],
            ]));

            $this->pusherServer->onMessage($connection, $message);
        }

        return $connection;
    }

    protected function getChannel(ConnectionInterface $connection, string $channelName)
    {
        return $this->channelManager->findOrCreate($connection->app->id, $channelName);
    }

    protected function markTestAsPassed()
    {
        $this->assertTrue(true);
    }
}