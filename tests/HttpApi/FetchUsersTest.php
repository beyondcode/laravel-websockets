<?php

namespace BeyondCode\LaravelWebSockets\Tests\HttpApi;

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

        $auth_key = 'TestKey';
        $auth_timestamp = time();
        $auth_version = '1.0';

        $queryParameters = http_build_query(compact('auth_key', 'auth_timestamp', 'auth_version'));

        $signature =
            "GET\n/apps/1234/channel/my-channel/users\n".
            "auth_key={$auth_key}".
            "&auth_timestamp={$auth_timestamp}".
            "&auth_version={$auth_version}";

        $auth_signature = hash_hmac('sha256', $signature, 'TestSecret');

        $request = new Request('GET', "/apps/1234/channel/my-channel/users?appId=1234&channelName=my-channel&auth_signature={$auth_signature}&{$queryParameters}");

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

        $auth_key = 'TestKey';
        $auth_timestamp = time();
        $auth_version = '1.0';

        $queryParameters = http_build_query(compact('auth_key', 'auth_timestamp', 'auth_version'));

        $signature =
            "GET\n/apps/1234/channel/my-channel/users\n".
            "auth_key={$auth_key}".
            "&auth_timestamp={$auth_timestamp}".
            "&auth_version={$auth_version}";

        $auth_signature = hash_hmac('sha256', $signature, 'TestSecret');

        $request = new Request('GET', "/apps/1234/channel/my-channel/users?appId=1234&channelName=invalid-channel&auth_signature={$auth_signature}&{$queryParameters}");

        $controller = app(FetchUsersController::class);

        $controller->onOpen($connection, $request);
    }

    /** @test */
    public function it_returns_connected_user_information()
    {
        $this->joinPresenceChannel('presence-channel');

        $connection = new Connection();

        $auth_key = 'TestKey';
        $auth_timestamp = time();
        $auth_version = '1.0';

        $queryParameters = http_build_query(compact('auth_key', 'auth_timestamp', 'auth_version'));

        $signature =
            "GET\n/apps/1234/channel/my-channel/users\n".
            "auth_key={$auth_key}".
            "&auth_timestamp={$auth_timestamp}".
            "&auth_version={$auth_version}";

        $auth_signature = hash_hmac('sha256', $signature, 'TestSecret');

        $request = new Request('GET', "/apps/1234/channel/my-channel/users?appId=1234&channelName=presence-channel&auth_signature={$auth_signature}&{$queryParameters}");

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
