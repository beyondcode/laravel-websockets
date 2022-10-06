<?php

namespace BeyondCode\LaravelWebSockets\Test;

use BeyondCode\LaravelWebSockets\API\FetchChannel;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\JsonResponse;
use Pusher\Pusher;

class FetchChannelTest extends TestCase
{
    public function test_invalid_signatures_can_not_access_the_api()
    {
        $this->startServer();

        $requestPath = '/apps/1234/channels/my-channel';

        $queryString = http_build_query(Pusher::build_auth_query_params(
            'TestKey', 'InvalidSecret', 'GET', $requestPath
        ));

        $request = new Request('GET', "{$requestPath}?{$queryString}");

        $response = $this->await($this->browser->get('http://localhost:4000'."{$requestPath}?{$queryString}"));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('{"error":"Invalid auth signature provided."}', $response->getBody()->getContents());
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

        $queryString = http_build_query(Pusher::build_auth_query_params('TestKey', 'TestSecret', 'GET', $requestPath));

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
        $this->newPresenceConnection('presence-channel', ['user_id' => 1]);
        $this->newPresenceConnection('presence-channel', ['user_id' => 2]);

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/channel/my-channel';

        $routeParams = [
            'appId' => '1234',
            'channelName' => 'presence-channel',
        ];

        $queryString = http_build_query(Pusher::build_auth_query_params('TestKey', 'TestSecret', 'GET', $requestPath));

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
        $this->skipOnRedisReplication();

        $this->startServer();

        $this->newActiveConnection(['my-channel']);

        $requestPath = '/apps/1234/channels/invalid-channel';

        $queryString = http_build_query(Pusher::build_auth_query_params('TestKey', 'TestSecret', 'GET', $requestPath));

        $response = $this->await($this->browser->get('http://localhost:4000'."{$requestPath}?{$queryString}"));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('{"error":"Unknown channel `invalid-channel`."}', $response->getBody()->getContents());
    }
}
