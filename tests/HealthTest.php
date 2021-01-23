<?php

namespace BeyondCode\LaravelWebSockets\Test;

use BeyondCode\LaravelWebSockets\Server\HealthHandler;
use Illuminate\Support\Str;

class HealthTest extends TestCase
{
    public function test_health_handler()
    {
        $connection = $this->newConnection();

        $this->pusherServer = app(HealthHandler::class);

        $this->pusherServer->onOpen($connection);

        $this->assertTrue(
            Str::contains($connection->sentRawData[0], '{"ok":true}')
        );
    }
}
