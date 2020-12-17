<?php

namespace BeyondCode\LaravelWebSockets\Test;

use BeyondCode\LaravelWebSockets\API\FetchUsers;
use GuzzleHttp\Psr7\Request;
use Pusher\Pusher;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FetchUsersTest extends TestCase
{
    public function test_invalid_signatures_can_not_access_the_api()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Invalid auth signature provided.');

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/channel/my-channel';

        $routeParams = [
            'appId' => '1234',
            'channelName' => 'my-channel',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'InvalidSecret', 'GET', $requestPath
        );

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchUsers::class);

        $controller->onOpen($connection, $request);
    }

    public function test_it_only_returns_data_for_presence_channels()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Invalid presence channel');

        $this->newActiveConnection(['my-channel']);

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/channel/my-channel/users';

        $routeParams = [
            'appId' => '1234',
            'channelName' => 'my-channel',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'GET', $requestPath
        );

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchUsers::class);

        $controller->onOpen($connection, $request);
    }

    public function test_it_returns_404_for_invalid_channels()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Invalid presence channel');

        $this->newActiveConnection(['my-channel']);

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/channel/invalid-channel/users';

        $routeParams = [
            'appId' => '1234',
            'channelName' => 'invalid-channel',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'GET', $requestPath
        );

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchUsers::class);

        $controller->onOpen($connection, $request);
    }

    public function test_it_returns_connected_user_information()
    {
        $this->newPresenceConnection('presence-channel');

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/channel/presence-channel/users';

        $routeParams = [
            'appId' => '1234',
            'channelName' => 'presence-channel',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchUsers::class);

        $controller->onOpen($connection, $request);

        /** @var \Illuminate\Http\JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([
            'users' => [['id' => 1]],
        ], json_decode($response->getContent(), true));
    }

    public function test_multiple_clients_with_same_id_gets_counted_once()
    {
        $rick = $this->newPresenceConnection('presence-channel', ['user_id' => 1]);
        $morty = $this->newPresenceConnection('presence-channel', ['user_id' => 1]);

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/channel/presence-channel/users';

        $routeParams = [
            'appId' => '1234',
            'channelName' => 'presence-channel',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchUsers::class);

        $controller->onOpen($connection, $request);

        /** @var \Illuminate\Http\JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([
            'users' => [['id' => 1]],
        ], json_decode($response->getContent(), true));
    }
}
