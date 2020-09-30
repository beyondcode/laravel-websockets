<?php

namespace BeyondCode\LaravelWebSockets\Test;

use BeyondCode\LaravelWebSockets\API\FetchChannels;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\JsonResponse;
use Pusher\Pusher;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FetchChannelsTest extends TestCase
{
    public function test_invalid_signatures_can_not_access_the_api()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Invalid auth signature provided.');

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/channels';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'InvalidSecret', 'GET', $requestPath
        );

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannels::class);

        $controller->onOpen($connection, $request);
    }

    public function test_it_returns_the_channel_information()
    {
        $this->newPresenceConnection('presence-channel');

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/channels';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'GET', $requestPath
        );

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannels::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([
            'channels' => [
                'presence-channel' => [],
            ],
        ], json_decode($response->getContent(), true));
    }

    public function test_it_returns_the_channel_information_for_prefix()
    {
        $this->newPresenceConnection('presence-global.1');
        $this->newPresenceConnection('presence-global.1');
        $this->newPresenceConnection('presence-global.2');
        $this->newPresenceConnection('presence-notglobal.2');

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/channels';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath, [
            'filter_by_prefix' => 'presence-global',
        ]);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannels::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([
            'channels' => [
                'presence-global.1' => [],
                'presence-global.2' => [],
            ],
        ], json_decode($response->getContent(), true));
    }

    public function test_it_returns_the_channel_information_for_prefix_with_user_count()
    {
        $this->newPresenceConnection('presence-global.1', ['user_id' => 1]);
        $this->newPresenceConnection('presence-global.1', ['user_id' => 2]);
        $this->newPresenceConnection('presence-global.2', ['user_id' => 3]);
        $this->newPresenceConnection('presence-notglobal.2', ['user_id' => 4]);

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/channels';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath, [
            'filter_by_prefix' => 'presence-global',
            'info' => 'user_count',
        ]);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannels::class);

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

    public function test_can_not_get_non_presence_channel_user_count()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Request must be limited to presence channels in order to fetch user_count');

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/channels';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath, [
            'info' => 'user_count',
        ]);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannels::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);
    }

    public function test_it_returns_empty_object_for_no_channels_found()
    {
        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/channels';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannels::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame('{"channels":{}}', $response->getContent());
    }
}
