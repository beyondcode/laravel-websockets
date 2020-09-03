<?php

namespace BeyondCode\LaravelWebSockets\Tests\HttpApi;

use BeyondCode\LaravelWebSockets\HttpApi\Controllers\FetchChannelsController;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Connection;
use BeyondCode\LaravelWebSockets\Tests\TestCase;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\JsonResponse;
use Pusher\Pusher;

class FetchChannelsReplicationTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->runOnlyOnRedisReplication();
    }

    /** @test */
    public function replication_it_returns_the_channel_information()
    {
        $this->joinPresenceChannel('presence-channel');

        $connection = new Connection();

        $requestPath = '/apps/1234/channels';
        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannelsController::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->getSubscribeClient()
            ->assertEventDispatched('message');

        $this->getPublishClient()
            ->assertNotCalled('hset')
            ->assertCalledWithArgs('hgetall', ['laravel_database_1234:presence-channel'])
            ->assertCalled('publish')
            ->assertCalled('multi')
            ->assertCalledWithArgs('hlen', ['laravel_database_1234:presence-channel'])
            ->assertCalled('exec');
    }

    /** @test */
    public function replication_it_returns_the_channel_information_for_prefix()
    {
        $this->joinPresenceChannel('presence-global.1');
        $this->joinPresenceChannel('presence-global.1');
        $this->joinPresenceChannel('presence-global.2');
        $this->joinPresenceChannel('presence-notglobal.2');

        $connection = new Connection();

        $requestPath = '/apps/1234/channels';
        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath, [
            'filter_by_prefix' => 'presence-global',
        ]);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannelsController::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->getSubscribeClient()
            ->assertEventDispatched('message');

        $this->getPublishClient()
            ->assertNotCalled('hset')
            ->assertCalledWithArgs('hgetall', ['laravel_database_1234:presence-global.1'])
            ->assertCalledWithArgs('hgetall', ['laravel_database_1234:presence-global.2'])
            ->assertCalledWithArgs('hgetall', ['laravel_database_1234:presence-notglobal.2'])
            ->assertCalled('publish')
            ->assertCalled('multi')
            ->assertCalledWithArgs('hlen', ['laravel_database_1234:presence-global.1'])
            ->assertCalledWithArgs('hlen', ['laravel_database_1234:presence-global.2'])
            ->assertNotCalledWithArgs('hlen', ['laravel_database_1234:presence-notglobal.2'])
            ->assertCalled('exec');
    }

    /** @test */
    public function replication_it_returns_the_channel_information_for_prefix_with_user_count()
    {
        $this->joinPresenceChannel('presence-global.1');
        $this->joinPresenceChannel('presence-global.1');
        $this->joinPresenceChannel('presence-global.2');
        $this->joinPresenceChannel('presence-notglobal.2');

        $connection = new Connection();

        $requestPath = '/apps/1234/channels';
        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath, [
            'filter_by_prefix' => 'presence-global',
            'info' => 'user_count',
        ]);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannelsController::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->getSubscribeClient()
            ->assertEventDispatched('message');

        $this->getPublishClient()
            ->assertNotCalled('hset')
            ->assertCalledWithArgs('hgetall', ['laravel_database_1234:presence-global.1'])
            ->assertCalledWithArgs('hgetall', ['laravel_database_1234:presence-global.2'])
            ->assertCalledWithArgs('hgetall', ['laravel_database_1234:presence-notglobal.2'])
            ->assertCalled('publish')
            ->assertCalled('multi')
            ->assertCalledWithArgs('hlen', ['laravel_database_1234:presence-global.1'])
            ->assertCalledWithArgs('hlen', ['laravel_database_1234:presence-global.2'])
            ->assertNotCalledWithArgs('hlen', ['laravel_database_1234:presence-notglobal.2'])
            ->assertCalled('exec');
    }

    /** @test */
    public function replication_it_returns_empty_object_for_no_channels_found()
    {
        $connection = new Connection();

        $requestPath = '/apps/1234/channels';
        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannelsController::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->getSubscribeClient()
            ->assertEventDispatched('message');

        $this->getPublishClient()
            ->assertNotCalled('hset')
            ->assertNotCalled('hgetall')
            ->assertNotCalled('publish')
            ->assertCalled('multi')
            ->assertNotCalled('hlen')
            ->assertCalled('exec');
    }
}
