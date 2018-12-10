<?php

namespace BeyondCode\LaravelWebSockets\Tests\HttpApi;

use Pusher\Pusher;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\JsonResponse;
use BeyondCode\LaravelWebSockets\Tests\TestCase;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Connection;
use Symfony\Component\HttpKernel\Exception\HttpException;
use BeyondCode\LaravelWebSockets\HttpApi\Controllers\FetchChannelsController;

class FetchChannelsTest extends TestCase
{
    /** @test */
    public function invalid_signatures_can_not_access_the_api()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Invalid auth signature provided.');

        $connection = new Connection();

        $requestPath = '/apps/1234/channels';
        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'InvalidSecret', 'GET', $requestPath);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannelsController::class);

        $controller->onOpen($connection, $request);
    }

    /** @test */
    public function it_returns_the_channel_information()
    {
        $this->joinPresenceChannel('presence-channel');
        $this->joinPresenceChannel('presence-channel');
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

        $this->assertSame([
            'channels' => [
                'presence-channel' => [
                    'user_count' => 3,
                ],
            ],
        ], json_decode($response->getContent(), true));
    }

    /** @test */
    public function it_returns_the_channel_information_for_prefix()
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

        $this->assertSame([
            'channels' => [
                'presence-global.1' => [
                    'user_count' => 2,
                ],
                'presence-global.2' => [
                    'user_count' => 1,
                ],
            ],
        ], json_decode($response->getContent(), true));
    }

    /** @test */
    public function it_returns_empty_object_for_no_channels_found()
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

        $this->assertSame('{"channels":{}}', $response->getContent());
    }
}
