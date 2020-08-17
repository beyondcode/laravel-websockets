<?php

namespace BeyondCode\LaravelWebSockets\Tests\HttpApi;

use BeyondCode\LaravelWebSockets\HttpApi\Controllers\FetchChannelController;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Connection;
use BeyondCode\LaravelWebSockets\Tests\TestCase;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\JsonResponse;
use Pusher\Pusher;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FetchChannelReplicationTest extends TestCase
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
    public function replication_invalid_signatures_can_not_access_the_api()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Invalid auth signature provided.');

        $connection = new Connection();

        $requestPath = '/apps/1234/channel/my-channel';
        $routeParams = [
            'appId' => '1234',
            'channelName' => 'my-channel',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'InvalidSecret', 'GET', $requestPath);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannelController::class);

        $controller->onOpen($connection, $request);
    }

    /** @test */
    public function replication_it_returns_the_channel_information()
    {
        $this->getConnectedWebSocketConnection(['my-channel']);
        $this->getConnectedWebSocketConnection(['my-channel']);

        $connection = new Connection();

        $requestPath = '/apps/1234/channel/my-channel';
        $routeParams = [
            'appId' => '1234',
            'channelName' => 'my-channel',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannelController::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([
            'occupied' => true,
            'subscription_count' => 2,
        ], json_decode($response->getContent(), true));
    }

    /** @test */
    public function replication_it_returns_presence_channel_information()
    {
        $this->skipOnRedisReplication();

        $this->joinPresenceChannel('presence-channel');
        $this->joinPresenceChannel('presence-channel');

        $connection = new Connection();

        $requestPath = '/apps/1234/channel/my-channel';
        $routeParams = [
            'appId' => '1234',
            'channelName' => 'presence-channel',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannelController::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->getSubscribeClient()
            ->assertEventDispatched('message');

        $this->getPublishClient()
            ->assertCalled('hset')
            ->assertCalled('hgetall')
            ->assertCalled('publish');

        $this->assertSame([
            'occupied' => true,
            'subscription_count' => 2,
            'user_count' => 2,
        ], json_decode($response->getContent(), true));
    }

    /** @test */
    public function replication_it_returns_404_for_invalid_channels()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unknown channel');

        $this->getConnectedWebSocketConnection(['my-channel']);

        $connection = new Connection();

        $requestPath = '/apps/1234/channel/invalid-channel';
        $routeParams = [
            'appId' => '1234',
            'channelName' => 'invalid-channel',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannelController::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([
            'occupied' => true,
            'subscription_count' => 2,
        ], json_decode($response->getContent(), true));
    }
}
