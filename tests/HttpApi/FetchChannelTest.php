<?php

namespace BeyondCode\LaravelWebSockets\Tests\HttpApi;

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

        $auth_key = 'TestKey';
        $auth_timestamp = time();
        $auth_version = '1.0';

        $queryParameters = http_build_query(compact('auth_key', 'auth_timestamp', 'auth_version'));

        $signature =
            "GET\n/apps/1234/channels\n".
            "auth_key={$auth_key}".
            "&auth_timestamp={$auth_timestamp}".
            "&auth_version={$auth_version}";

        $auth_signature = hash_hmac('sha256', $signature, 'InvalidSecret');

        $request = new Request('GET', "/apps/1234/channel/my-channel?appId=1234&channelName=my-channel&auth_signature={$auth_signature}&{$queryParameters}");

        $controller = app(FetchChannelController::class);

        $controller->onOpen($connection, $request);
    }

    /** @test */
    public function it_returns_the_channel_information()
    {
        $this->getConnectedWebSocketConnection(['my-channel']);
        $this->getConnectedWebSocketConnection(['my-channel']);

        $connection = new Connection();

        $auth_key = 'TestKey';
        $auth_timestamp = time();
        $auth_version = '1.0';

        $queryParameters = http_build_query(compact('auth_key', 'auth_timestamp', 'auth_version'));

        $signature =
            "GET\n/apps/1234/channel/my-channel\n".
            "auth_key={$auth_key}".
            "&auth_timestamp={$auth_timestamp}".
            "&auth_version={$auth_version}";

        $auth_signature = hash_hmac('sha256', $signature, 'TestSecret');

        $request = new Request('GET', "/apps/1234/channel/my-channel?appId=1234&channelName=my-channel&auth_signature={$auth_signature}&{$queryParameters}");

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

        $auth_key = 'TestKey';
        $auth_timestamp = time();
        $auth_version = '1.0';

        $queryParameters = http_build_query(compact('auth_key', 'auth_timestamp', 'auth_version'));

        $signature =
            "GET\n/apps/1234/channel/my-channel\n".
            "auth_key={$auth_key}".
            "&auth_timestamp={$auth_timestamp}".
            "&auth_version={$auth_version}";

        $auth_signature = hash_hmac('sha256', $signature, 'TestSecret');

        $request = new Request('GET', "/apps/1234/channel/my-channel?appId=1234&channelName=invalid-channel&auth_signature={$auth_signature}&{$queryParameters}");

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
