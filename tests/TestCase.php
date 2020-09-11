<?php

namespace BeyondCode\LaravelWebSockets\Test;

use BeyondCode\LaravelWebSockets\Contracts\ChannelManager;
use BeyondCode\LaravelWebSockets\Contracts\StatisticsCollector;
use BeyondCode\LaravelWebSockets\Contracts\StatisticsStore;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Redis;
use Orchestra\Testbench\BrowserKit\TestCase as Orchestra;
use React\EventLoop\Factory as LoopFactory;

abstract class TestCase extends Orchestra
{
    /**
     * A test Pusher server.
     *
     * @var \BeyondCode\LaravelWebSockets\Server\WebSocketHandler
     */
    protected $pusherServer;

    /**
     * The test Channel manager.
     *
     * @var \BeyondCode\LaravelWebSockets\Contracts\ChannelManager
     */
    protected $channelManager;

    /**
     * The test Channel manager.
     *
     * @var \BeyondCode\LaravelWebSockets\Contracts\StatisticsCollector
     */
    protected $statisticsCollector;

    /**
     * The test Channel manager.
     *
     * @var \BeyondCode\LaravelWebSockets\Contracts\StatisticsStore
     */
    protected $statisticsStore;

    /**
     * Get the loop instance.
     *
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * The Redis manager instance.
     *
     * @var \Illuminate\Redis\RedisManager
     */
    protected $redis;

    /**
     * Get the replication mode it is used for testing.
     *
     * @var string
     */
    protected $replicationMode = 'local';

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->loop = LoopFactory::create();

        $this->replicationMode = getenv('REPLICATION_MODE') ?: 'local';

        $this->resetDatabase();
        $this->loadLaravelMigrations(['--database' => 'sqlite']);
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->withFactories(__DIR__.'/database/factories');

        $this->registerManagers();

        $this->registerStatisticsCollectors();

        $this->registerStatisticsStores();

        $this->pusherServer = $this->app->make(config('websockets.handlers.websocket'));

        if ($this->replicationMode === 'redis') {
            $this->registerRedis();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageProviders($app)
    {
        return [
            \BeyondCode\LaravelWebSockets\WebSocketsServiceProvider::class,
            TestServiceProvider::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironmentSetUp($app)
    {
        $this->replicationMode = getenv('REPLICATION_MODE') ?: 'local';

        $app['config']->set('database.default', 'sqlite');

        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => __DIR__.'/database.sqlite',
            'prefix' => '',
        ]);

        $app['config']->set(
            'broadcasting.connections.websockets', [
                'driver' => 'pusher',
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

        $app['config']->set('auth.providers.users.model', Models\User::class);

        $app['config']->set('app.key', 'wslxrEFGWY6GfGhvN9L3wH3KSRJQQpBD');

        $app['config']->set('database.redis.default', [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ]);

        $app['config']->set(
            'websockets.replication.mode', $this->replicationMode
        );

        if ($this->replicationMode === 'redis') {
            $app['config']->set('broadcasting.default', 'pusher');
            $app['config']->set('cache.default', 'redis');
        }

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
            [
                'name' => 'Test App 2',
                'id' => '12345',
                'key' => 'TestKey2',
                'secret' => 'TestSecret2',
                'host' => 'localhost',
                'capacity' => null,
                'enable_client_messages' => false,
                'enable_statistics' => true,
                'allowed_origins' => [],
            ],
        ]);

        $app['config']->set('websockets.replication.modes', [
            'local' => [
                'channel_manager' => \BeyondCode\LaravelWebSockets\ChannelManagers\LocalChannelManager::class,
                'collector' => \BeyondCode\LaravelWebSockets\Statistics\Collectors\MemoryCollector::class,
            ],
            'redis' => [
                'channel_manager' => \BeyondCode\LaravelWebSockets\ChannelManagers\RedisChannelManager::class,
                'connection' => 'default',
                'collector' => \BeyondCode\LaravelWebSockets\Statistics\Collectors\RedisCollector::class,
            ],
        ]);
    }

    /**
     * Register the managers that are not resolved
     * by the package service provider.
     *
     * @return void
     */
    protected function registerManagers()
    {
        $this->app->singleton(ChannelManager::class, function () {
            $mode = config('websockets.replication.mode', $this->replicationMode);

            $class = config("websockets.replication.modes.{$mode}.channel_manager");

            return new $class($this->loop, Mocks\RedisFactory::class);
        });

        $this->channelManager = $this->app->make(ChannelManager::class);
    }

    /**
     * Register the statistics collectors that are
     * not resolved by the package service provider.
     *
     * @return void
     */
    protected function registerStatisticsCollectors()
    {
        $this->app->singleton(StatisticsCollector::class, function () {
            $mode = config('websockets.replication.mode', $this->replicationMode);

            $class = config("websockets.replication.modes.{$mode}.collector");

            return new $class;
        });

        $this->statisticsCollector = $this->app->make(StatisticsCollector::class);

        $this->statisticsCollector->flush();
    }

    /**
     * Register the statistics stores that are
     * not resolved by the package service provider.
     *
     * @return void
     */
    protected function registerStatisticsStores()
    {
        $this->app->singleton(StatisticsStore::class, function () {
            $class = config('websockets.statistics.store');

            return new $class;
        });

        $this->statisticsStore = $this->app->make(StatisticsStore::class);
    }

    /**
     * Register the Redis components for testing.
     *
     * @return void
     */
    protected function registerRedis()
    {
        $this->redis = Redis::connection();

        $this->redis->flushdb();
    }

    /**
     * Get the websocket connection for a specific key.
     *
     * @param  string  $appKey
     * @param  array  $headers
     * @return Mocks\Connection
     */
    protected function newConnection(string $appKey = 'TestKey', array $headers = [])
    {
        $connection = new Mocks\Connection;

        $connection->httpRequest = new Request('GET', "/?appKey={$appKey}", $headers);

        return $connection;
    }

    /**
     * Get a connected websocket connection.
     *
     * @param  array  $channelsToJoin
     * @param  string  $appKey
     * @param  array  $headers
     * @return Mocks\Connection
     */
    protected function newActiveConnection(array $channelsToJoin = [], string $appKey = 'TestKey', array $headers = [])
    {
        $connection = $this->newConnection($appKey, $headers);

        $this->pusherServer->onOpen($connection);

        foreach ($channelsToJoin as $channel) {
            $message = new Mocks\Message([
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
     * @param  array  $user
     * @return Mocks\Connection
     */
    protected function newPresenceConnection($channel, array $user = [])
    {
        $connection = $this->newConnection();

        $this->pusherServer->onOpen($connection);

        $user = $user ?: [
            'user_id' => 1,
            'user_info' => ['name' => 'Rick'],
        ];

        $signature = "{$connection->socketId}:{$channel}:".json_encode($user);

        $hash = hash_hmac('sha256', $signature, $connection->app->secret);

        $message = new Mocks\Message([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => "{$connection->app->key}:{$hash}",
                'channel' => $channel,
                'channel_data' => json_encode($user),
            ],
        ]);

        $this->pusherServer->onMessage($connection, $message);

        return $connection;
    }

    /**
     * Join a private channel.
     *
     * @param  string  $channel
     * @return Mocks\Connection
     */
    protected function newPrivateConnection($channel)
    {
        $connection = $this->newConnection();

        $this->pusherServer->onOpen($connection);

        $signature = "{$connection->socketId}:{$channel}";

        $hash = hash_hmac('sha256', $signature, $connection->app->secret);

        $message = new Mocks\Message([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => "{$connection->app->key}:{$hash}",
                'channel' => $channel,
            ],
        ]);

        $this->pusherServer->onMessage($connection, $message);

        return $connection;
    }

    /**
     * Get the subscribed client for the replication.
     *
     * @return Mocks\LazyClient
     */
    protected function getSubscribeClient()
    {
        return $this->channelManager->getSubscribeClient();
    }

    /**
     * Get the publish client for the replication.
     *
     * @return Mocks\LazyClient
     */
    protected function getPublishClient()
    {
        return $this->channelManager->getPublishClient();
    }

    /**
     * Reset the database.
     *
     * @return void
     */
    protected function resetDatabase()
    {
        file_put_contents(__DIR__.'/database.sqlite', null);
    }

    protected function runOnlyOnRedisReplication()
    {
        if ($this->replicationMode !== 'redis') {
            $this->markTestSkipped('Skipped test because the replication mode is not set to Redis.');
        }
    }

    protected function runOnlyOnLocalReplication()
    {
        if ($this->replicationMode !== 'local') {
            $this->markTestSkipped('Skipped test because the replication mode is not set to Local.');
        }
    }

    protected function skipOnRedisReplication()
    {
        if ($this->replicationMode === 'redis') {
            $this->markTestSkipped('Skipped test because the replication mode is Redis.');
        }
    }

    protected function skipOnLocalReplication()
    {
        if ($this->replicationMode === 'local') {
            $this->markTestSkipped('Skipped test because the replication mode is Local.');
        }
    }
}
