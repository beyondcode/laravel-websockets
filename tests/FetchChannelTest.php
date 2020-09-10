<?php

namespace BeyondCode\LaravelWebSockets\Test;

use BeyondCode\LaravelWebSockets\API\FetchChannel;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\JsonResponse;
use Pusher\Pusher;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FetchChannelTest extends TestCase
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

        $controller = app(FetchChannel::class);

        $controller->onOpen($connection, $request);
    }

    public function test_it_returns_the_channel_information()
    {
        $this->newActiveConnection(['my-channel']);
        $this->newActiveConnection(['my-channel']);

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/channel/my-channel';
        $routeParams = [
            'appId' => '1234',
            'channelName' => 'my-channel',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannel::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([
            'occupied' => true,
            'subscription_count' => 2,
        ], json_decode($response->getContent(), true));
    }

    public function test_it_returns_presence_channel_information()
    {
        $this->newPresenceConnection('presence-channel');
        $this->newPresenceConnection('presence-channel');

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/channel/my-channel';

        $routeParams = [
            'appId' => '1234',
            'channelName' => 'presence-channel',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannel::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([
            'occupied' => true,
            'subscription_count' => 2,
            'user_count' => 2,
        ], json_decode($response->getContent(), true));
    }

    public function test_it_returns_404_for_invalid_channels()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unknown channel');

        $this->newActiveConnection(['my-channel']);

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/channel/invalid-channel';

        $routeParams = [
            'appId' => '1234',
            'channelName' => 'invalid-channel',
        ];

        $queryString = Pusher::build_auth_query_string('TestKey', 'TestSecret', 'GET', $requestPath);

        $request = new Request('GET', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(FetchChannel::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([
            'occupied' => true,
            'subscription_count' => 2,
        ], json_decode($response->getContent(), true));
    }
}
