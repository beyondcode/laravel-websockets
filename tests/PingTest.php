<?php

namespace BeyondCode\LaravelWebSockets\Test;

class PingTest extends TestCase
{
    public function test_ping_returns_pong()
    {
        $connection = $this->newActiveConnection(['public-channel']);

        $message = new Mocks\Message(['event' => 'pusher:ping']);

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher:pong');
    }
}
