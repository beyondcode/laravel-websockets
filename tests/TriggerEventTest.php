<?php

namespace BeyondCode\LaravelWebSockets\Test;

use BeyondCode\LaravelWebSockets\API\TriggerEvent;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\JsonResponse;
use Pusher\Pusher;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TriggerEventTest extends TestCase
{
    public function test_invalid_signatures_can_not_fire_the_event()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Invalid auth signature provided.');

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/events';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'InvalidSecret', 'GET', $requestPath
        );

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(TriggerEvent::class);

        $controller->onOpen($connection, $request);
    }

    public function test_it_fires_the_event_to_public_channel()
    {
        $this->newActiveConnection(['public-channel']);

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/events';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'GET', $requestPath, [
                'channels' => 'public-channel',
            ],
        );

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(TriggerEvent::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([], json_decode($response->getContent(), true));

        $this->statisticsCollector
            ->getAppStatistics('1234')
            ->then(function ($statistic) {
                $this->assertEquals([
                    'peak_connections_count' => 1,
                    'websocket_messages_count' => 1,
                    'api_messages_count' => 1,
                    'app_id' => '1234',
                ], $statistic->toArray());
            });
    }

    public function test_it_fires_the_event_to_presence_channel()
    {
        $this->newPresenceConnection('presence-channel');

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/events';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'GET', $requestPath, [
                'channels' => 'presence-channel',
            ],
        );

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(TriggerEvent::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([], json_decode($response->getContent(), true));

        $this->statisticsCollector
            ->getAppStatistics('1234')
            ->then(function ($statistic) {
                $this->assertEquals([
                    'peak_connections_count' => 1,
                    'websocket_messages_count' => 1,
                    'api_messages_count' => 1,
                    'app_id' => '1234',
                ], $statistic->toArray());
            });
    }

    public function test_it_fires_the_event_to_private_channel()
    {
        $this->newPresenceConnection('private-channel');

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/events';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'GET', $requestPath, [
                'channels' => 'private-channel',
            ],
        );

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(TriggerEvent::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([], json_decode($response->getContent(), true));

        $this->statisticsCollector
            ->getAppStatistics('1234')
            ->then(function ($statistic) {
                $this->assertEquals([
                    'peak_connections_count' => 1,
                    'websocket_messages_count' => 1,
                    'api_messages_count' => 1,
                    'app_id' => '1234',
                ], $statistic->toArray());
            });
    }

    public function test_it_fires_event_across_servers()
    {
        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/events';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'GET', $requestPath, [
                'channels' => 'public-channel',
            ],
        );

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(TriggerEvent::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([], json_decode($response->getContent(), true));

        if (method_exists($this->channelManager, 'getPublishClient')) {
            $this->channelManager
                ->getPublishClient()
                ->assertCalledWithArgs('publish', [
                    $this->channelManager->getRedisKey('1234', 'public-channel'),
                    json_encode([
                        'channel' => 'public-channel',
                        'event' => null,
                        'data' => null,
                        'appId' => '1234',
                        'serverId' => $this->channelManager->getServerId(),
                    ]),
                ]);
        }
    }
}
