<?php

namespace BeyondCode\LaravelWebSockets\Test;

use Pusher\Pusher;

class TriggerEventTest extends TestCase
{
    public function test_invalid_signatures_can_not_fire_the_event()
    {
        $this->startServer();

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/events';

        $queryString = http_build_query(Pusher::build_auth_query_params(
            'TestKey', 'InvalidSecret', 'GET', $requestPath
        ));

        $response = $this->await($this->browser->get('http://localhost:4000'."{$requestPath}?{$queryString}"));

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('', $response->getBody()->getContents());
    }
}
