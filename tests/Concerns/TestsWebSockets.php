<?php

namespace BeyondCode\LaravelWebSockets\Tests\Concerns;

use BeyondCode\LaravelWebSockets\Facades\StatisticsLogger;
use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;
use BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Connection;
use BeyondCode\LaravelWebSockets\Tests\Mocks\FakeMemoryStatisticsLogger;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Message;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use GuzzleHttp\Psr7\Request;
use Ratchet\ConnectionInterface;
use React\EventLoop\Factory as LoopFactory;

trait TestsWebSockets
{
    /**
     * A test Pusher server.
     *
     * @var \BeyondCode\LaravelWebSockets\WebSockets\WebSocketHandler
     */
    protected $pusherServer;

    /**
     * The test Channel manager.
     *
     * @var \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager
     */
    protected $channelManager;

    /**
     * The used statistics driver.
     *
     * @var \BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver
     */
    protected $statisticsDriver;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->pusherServer = $this->app->make(config('websockets.handlers.websocket'));

        $this->channelManager = $this->app->make(ChannelManager::class);

        $this->statisticsDriver = $this->app->make(StatisticsDriver::class);

        StatisticsLogger::swap(new FakeMemoryStatisticsLogger(
            $this->channelManager,
            $this->statisticsDriver
        ));

        $this->configurePubSub();
    }

    /**
     * Get the websocket connection for a specific URL.
     *
     * @param  mixed  $appKey
     * @param  array  $headers
     * @return \BeyondCode\LaravelWebSockets\Tests\Mocks\Connection
     */
    protected function getWebSocketConnection(string $appKey = 'TestKey', array $headers = []): Connection
    {
        $connection = new Connection;

        $connection->httpRequest = new Request('GET', "/?appKey={$appKey}", $headers);

        return $connection;
    }

    /**
     * Get a connected websocket connection.
     *
     * @param  array  $channelsToJoin
     * @param  string  $appKey
     * @param  array  $headers
     * @return \BeyondCode\LaravelWebSockets\Tests\Mocks\Connection
     */
    protected function getConnectedWebSocketConnection(array $channelsToJoin = [], string $appKey = 'TestKey', array $headers = []): Connection
    {
        $connection = new Connection;

        $connection->httpRequest = new Request('GET', "/?appKey={$appKey}", $headers);

        $this->pusherServer->onOpen($connection);

        foreach ($channelsToJoin as $channel) {
            $message = new Message([
                'event' => 'pusher:subscribe',
                'data' => [
                    'channel' => $channel,
                ],
            ]);

            $this->pusherServer->onMessage($connection, $message);
        }

        return $connection;
    }

    /**
     * Join a presence channel.
     *
     * @param  string  $channel
     * @return \BeyondCode\LaravelWebSockets\Tests\Mocks\Connection
     */
    protected function joinPresenceChannel($channel): Connection
    {
        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        $channelData = [
            'user_id' => 1,
            'user_info' => [
                'name' => 'Marcel',
            ],
        ];

        $signature = "{$connection->socketId}:{$channel}:".json_encode($channelData);

        $message = new Message([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => $connection->app->key.':'.hash_hmac('sha256', $signature, $connection->app->secret),
                'channel' => $channel,
                'channel_data' => json_encode($channelData),
            ],
        ]);

        $this->pusherServer->onMessage($connection, $message);

        return $connection;
    }

    /**
     * Get a channel from connection.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  string $channelName
     * @return \BeyondCode\LaravelWebSockets\WebSockets\Channels\Channel|null
     */
    protected function getChannel(ConnectionInterface $connection, string $channelName)
    {
        return $this->channelManager->findOrCreate($connection->app->id, $channelName);
    }

    /**
     * Configure the replicator clients.
     *
     * @return void
     */
    protected function configurePubSub()
    {
        // Replace the publish and subscribe clients with a Mocked
        // factory lazy instance on boot.
        $this->app->singleton(ReplicationInterface::class, function () {
            $driver = config('websockets.replication.driver', 'local');

            $client = config(
                "websockets.replication.{$driver}.client",
                \BeyondCode\LaravelWebSockets\PubSub\Drivers\LocalClient::class
            );

            return (new $client)->boot(
                LoopFactory::create(), Mocks\RedisFactory::class
            );
        });
    }

    /**
     * Get the subscribed client for the replication.
     *
     * @return ReplicationInterface
     */
    protected function getSubscribeClient()
    {
        return $this->app
            ->make(ReplicationInterface::class)
            ->getSubscribeClient();
    }

    /**
     * Get the publish client for the replication.
     *
     * @return ReplicationInterface
     */
    protected function getPublishClient()
    {
        return $this->app
            ->make(ReplicationInterface::class)
            ->getPublishClient();
    }
}
