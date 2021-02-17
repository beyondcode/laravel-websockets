<?php

namespace BeyondCode\LaravelWebSockets\Test;

use BeyondCode\LaravelWebSockets\API\FetchUsers;
use BeyondCode\LaravelWebSockets\Facades\WebSocketRouter;
use BeyondCode\LaravelWebSockets\Server\Loggers\HttpLogger;
use BeyondCode\LaravelWebSockets\Server\Loggers\WebSocketsLogger;
use BeyondCode\LaravelWebSockets\ServerFactory;
use Clue\React\Buzz\Browser;
use Clue\React\Buzz\Message\ResponseException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Http;
use Pusher\Pusher;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\Exception\HttpException;
use function Clue\React\Block\await;

class FetchUsersTest extends TestCase
{
    public function test_invalid_signatures_can_not_access_the_api()
    {
        $this->startServer();

        $requestPath = '/apps/1234/channels/my-channel/users';

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'InvalidSecret', 'GET', $requestPath
        );

        $response = $this->await($this->browser->get('http://localhost:4000' . "{$requestPath}?{$queryString}"));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('{"error":"Invalid auth signature provided."}', $response->getBody()->getContents());
    }

    public function test_it_only_returns_data_for_presence_channels()
    {
        $this->startServer();

        $requestPath = '/apps/1234/channels/my-channel/users';

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'GET', $requestPath
        );

        $response = $this->await($this->browser->get('http://localhost:4000' . "{$requestPath}?{$queryString}"));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('{"error":"Invalid presence channel `my-channel`"}', $response->getBody()->getContents());
    }

    public function test_it_returns_400_for_invalid_channels()
    {
        $this->startServer();

        $requestPath = '/apps/1234/channels/invalid-channel/users';

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'GET', $requestPath
        );

        $response = $this->await($this->browser->get('http://localhost:4000' . "{$requestPath}?{$queryString}"));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('{"error":"Invalid presence channel `invalid-channel`"}', $response->getBody()->getContents());
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
