<?php

namespace BeyondCode\LaravelWebsockets\Tests\Channels;

use BeyondCode\LaravelWebSockets\Tests\Mocks\Message;
use BeyondCode\LaravelWebSockets\Tests\TestCase;

class ChannelTest extends TestCase
{
    /** @test */
    public function clients_can_subscribe_to_channels()
    {
        $connection = $this->getWebSocketConnection();

        $message = new Message(json_encode([
            'event' => 'pusher:subscribe',
            'data' => [
                'channel' => 'basic-channel'
            ],
        ]));

        $this->pusherServer->onOpen($connection);

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'basic-channel'
        ]);
    }
}