<?php

namespace BeyondCode\LaravelWebSockets\Tests\HttpApi;

use Pusher\Pusher;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\JsonResponse;
use BeyondCode\LaravelWebSockets\Tests\TestCase;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Connection;
use Symfony\Component\HttpKernel\Exception\HttpException;
use BeyondCode\LaravelWebSockets\HttpApi\Controllers\FetchChannelController;

class FetchChannelTest extends TestCase
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

        $controller = app(FetchChannelController::class);

        $controller->onOpen($connection, $request);
    }

    /** @test */
    public function it_returns_the_channel_information()
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
    public function it_returns_404_for_invalid_channels()
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
