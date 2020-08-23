<?php

namespace BeyondCode\LaravelWebSockets\Tests;

use BeyondCode\LaravelWebSockets\Facades\StatisticsLogger;
use BeyondCode\LaravelWebSockets\PubSub\Drivers\LocalClient;
use BeyondCode\LaravelWebSockets\PubSub\Drivers\RedisClient;
use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;
use BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Connection;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Message;
use BeyondCode\LaravelWebSockets\Tests\Statistics\Logger\FakeStatisticsLogger;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use GuzzleHttp\Psr7\Request;
use Orchestra\Testbench\BrowserKit\TestCase as BaseTestCase;
use Ratchet\ConnectionInterface;
use React\EventLoop\Factory as LoopFactory;

abstract class TestCase extends BaseTestCase
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

        StatisticsLogger::swap(new FakeStatisticsLogger(
            $this->channelManager,
            app(StatisticsDriver::class)
        ));

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->configurePubSub();
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageProviders($app)
    {
        return [
            \BeyondCode\LaravelWebSockets\WebSocketsServiceProvider::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'wslxrEFGWY6GfGhvN9L3wH3KSRJQQpBD');

        $app['config']->set('websockets.apps', [
            [
                'name' => 'Test App',
                'id' => '1234',
                'key' => 'TestKey',
                'secret' => 'TestSecret',
                'host' => 'localhost',
                'capacity' => null,
                'enable_client_messages' => false,
                'enable_statistics' => true,
                'allowed_origins' => [],
            ],
            [
                'name' => 'Origin Test App',
                'id' => '1234',
                'key' => 'TestOrigin',
                'secret' => 'TestSecret',
                'capacity' => null,
                'enable_client_messages' => false,
                'enable_statistics' => true,
                'allowed_origins' => [
                    'test.origin.com',
                ],
            ],
        ]);

        $app['config']->set('database.redis.default', [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ]);

        $replicationDriver = getenv('REPLICATION_DRIVER') ?: 'local';

        $app['config']->set(
            'websockets.replication.driver', $replicationDriver
        );

        $app['config']->set(
            'broadcasting.connections.websockets', [
                'driver' => 'websockets',
                'key' => 'TestKey',
                'secret' => 'TestSecret',
                'app_id' => '1234',
                'options' => [
                    'cluster' => 'mt1',
                    'encrypted' => true,
                    'host' => '127.0.0.1',
                    'port' => 6001,
                    'scheme' => 'http',
                ],
            ]
        );

        if (in_array($replicationDriver, ['redis'])) {
            $app['config']->set('broadcasting.default', 'websockets');
        }
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
        if (config('websockets.replication.driver') === 'redis') {
            $this->app->singleton(ReplicationInterface::class, function () {
                return (new RedisClient)->boot(
                    LoopFactory::create(), Mocks\RedisFactory::class
                );
            });
        }

        if (config('websockets.replication.driver') === 'local') {
            $this->app->singleton(ReplicationInterface::class, function () {
                return new LocalClient;
            });
        }
    }

    protected function runOnlyOnRedisReplication()
    {
        if (config('websockets.replication.driver') !== 'redis') {
            $this->markTestSkipped('Skipped test because the replication driver is not set to Redis.');
        }
    }

    protected function runOnlyOnLocalReplication()
    {
        if (config('websockets.replication.driver') !== 'local') {
            $this->markTestSkipped('Skipped test because the replication driver is not set to Local.');
        }
    }

    protected function skipOnRedisReplication()
    {
        if (config('websockets.replication.driver') === 'redis') {
            $this->markTestSkipped('Skipped test because the replication driver is Redis.');
        }
    }

    protected function skipOnLocalReplication()
    {
        if (config('websockets.replication.driver') === 'local') {
            $this->markTestSkipped('Skipped test because the replication driver is Local.');
        }
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
