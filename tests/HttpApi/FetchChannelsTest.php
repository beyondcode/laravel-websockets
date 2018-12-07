<?php

namespace BeyondCode\LaravelWebSockets\Tests\HttpApi;

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

        $request = new Request('GET', "/apps/1234/channels?appId=1234&auth_signature={$auth_signature}&{$queryParameters}");

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

        $auth_key = 'TestKey';
        $auth_timestamp = time();
        $auth_version = '1.0';

        $queryParameters = http_build_query(compact('auth_key', 'auth_timestamp', 'auth_version'));

        $signature =
            "GET\n/apps/1234/channels\n".
            "auth_key={$auth_key}".
            "&auth_timestamp={$auth_timestamp}".
            "&auth_version={$auth_version}";

        $auth_signature = hash_hmac('sha256', $signature, 'TestSecret');

        $request = new Request('GET', "/apps/1234/channels?appId=1234&auth_signature={$auth_signature}&{$queryParameters}");

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

        $auth_key = 'TestKey';
        $auth_timestamp = time();
        $auth_version = '1.0';

        $queryParameters = http_build_query(compact('auth_key', 'auth_timestamp', 'auth_version'));

        $signature =
            "GET\n/apps/1234/channels\n".
            'filter_by_prefix=presence-global' .
            "auth_key={$auth_key}".
            "&auth_timestamp={$auth_timestamp}".
            "&auth_version={$auth_version}";

        $auth_signature = hash_hmac('sha256', $signature, 'TestSecret');

        $request = new Request('GET', "/apps/1234/channels?filter_by_prefix=presence-global&appId=1234&auth_signature={$auth_signature}&{$queryParameters}");

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
}
