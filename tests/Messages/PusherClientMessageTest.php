<?php

namespace BeyondCode\LaravelWebsockets\Tests\Messages;

use BeyondCode\LaravelWebSockets\Tests\TestCase;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Message;

class PusherClientMessageTest extends TestCase
{
    /** @test */
    public function client_messages_do_not_work_when_disabled()
    {
        $connection = $this->getConnectedWebSocketConnection(['test-channel']);

        $message = new Message(json_encode([
            'event' => 'client-test',
            'channel' => 'test-channel',
            'data' => [
                'client-event' => 'test'
            ],
        ]));

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertNotSentEvent('client-test');
    }
}