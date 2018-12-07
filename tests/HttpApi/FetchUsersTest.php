<?php

namespace BeyondCode\LaravelWebSockets\Tests\HttpApi;

use Pusher\Pusher;
use GuzzleHttp\Psr7\Request;
use BeyondCode\LaravelWebSockets\Tests\TestCase;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Connection;
use Symfony\Component\HttpKernel\Exception\HttpException;
use BeyondCode\LaravelWebSockets\HttpApi\Controllers\FetchUsersController;

class FetchUsersTest extends TestCase
{
    /** @test */
    public function invalid_signatures_can_not_access_the_api()
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

        $controller = app(FetchUsersController::class);

        $controller->onOpen($connection, $request);
    }

    /** @test */
    public function it_only_returns_data_for_presence_channels()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Invalid presence channel');

        $this->getConnectedWebSocketConnection(['my-channel']);

        $connection = new Connection();

        $requestPath = '/apps/1234/channel/my-channel/users';
        $routeParams = [
            'appId' => '1234',
            'channelName' => 'my-channel',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchUsersController::class);

        $controller->onOpen($connection, $request);
    }

    /** @test */
    public function it_returns_404_for_invalid_channels()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unknown channel');

        $this->getConnectedWebSocketConnection(['my-channel']);

        $connection = new Connection();

        $requestPath = '/apps/1234/channel/invalid-channel/users';
        $routeParams = [
            'appId' => '1234',
            'channelName' => 'invalid-channel',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchUsersController::class);

        $controller->onOpen($connection, $request);
    }

    /** @test */
    public function it_returns_connected_user_information()
    {
        $this->joinPresenceChannel('presence-channel');

        $connection = new Connection();

        $requestPath = '/apps/1234/channel/presence-channel/users';
        $routeParams = [
            'appId' => '1234',
            'channelName' => 'presence-channel',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchUsersController::class);

        $controller->onOpen($connection, $request);

        /** @var \Illuminate\Http\JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([
            'users' => [
                [
                    'id' => 1,
                ],
            ],
        ], json_decode($response->getContent(), true));
    }
}
