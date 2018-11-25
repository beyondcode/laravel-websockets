<?php

namespace BeyondCode\LaravelWebSockets\Tests;

use GuzzleHttp\Psr7\Request;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Connection;
use BeyondCode\LaravelWebSockets\LaravelWebSocketsServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaravelWebSocketsServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('websockets.clients', [
            [
                'name' => 'Test Client',
                'app_id' => 1234,
                'app_key' => 'TestKey',
                'app_secret' => 'TestSecret'
            ]
        ]);
    }

    protected function getWebSocketConnection(string $url): Connection
    {
        $connection = new Connection();

        $connection->httpRequest = new Request('GET', $url);

        return $connection;
    }
}