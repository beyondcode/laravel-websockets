<?php

namespace BeyondCode\LaravelWebSockets\Tests;

use Orchestra\Testbench\BrowserKit\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use Concerns\TestsWebSockets;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->resetDatabase();

        $this->loadLaravelMigrations(['--database' => 'sqlite']);

        $this->withFactories(__DIR__.'/database/factories');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
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
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'wslxrEFGWY6GfGhvN9L3wH3KSRJQQpBD');

        $app['config']->set('auth.providers.users.model', Models\User::class);

        $app['config']->set('database.default', 'sqlite');

        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => __DIR__.'/database.sqlite',
            'prefix'   => '',
        ]);

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

        if (in_array($replicationDriver, ['redis'])) {
            $app['config']->set('broadcasting.default', 'pusher');
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
     * Reset the database.
     *
     * @return void
     */
    protected function resetDatabase()
    {
        file_put_contents(__DIR__.'/database.sqlite', null);
    }
}
