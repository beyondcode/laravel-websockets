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
}
