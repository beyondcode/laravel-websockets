<?php

namespace BeyondCode\LaravelWebSockets\Tests\Messages;

use BeyondCode\LaravelWebSockets\Tests\Mocks\Message;
use BeyondCode\LaravelWebSockets\Tests\TestCase;

class PusherClientMessageTest extends TestCase
{
    /** @test */
    public function client_messages_do_not_work_when_disabled()
    {
        $connection = $this->getConnectedWebSocketConnection(['test-channel']);

        $message = new Message([
            'event' => 'client-test',
            'channel' => 'test-channel',
            'data' => [
                'client-event' => 'test',
            ],
        ]);

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertNotSentEvent('client-test');
    }

    /** @test */
    public function client_messages_get_broadcasted_when_enabled()
    {
        $this->app['config']->set('websockets.apps', [
            [
                'name' => 'Test App',
                'id' => 1234,
                'key' => 'TestKey',
                'secret' => 'TestSecret',
                'enable_client_messages' => true,
                'enable_statistics' => true,
            ],
        ]);

        $connection1 = $this->getConnectedWebSocketConnection(['test-channel']);
        $connection2 = $this->getConnectedWebSocketConnection(['test-channel']);

        $message = new Message([
            'event' => 'client-test',
            'channel' => 'test-channel',
            'data' => [
                'client-event' => 'test',
            ],
        ]);

        $this->pusherServer->onMessage($connection1, $message);

        $connection1->assertNotSentEvent('client-test');

        $connection2->assertSentEvent('client-test', [
            'data' => [
                'client-event' => 'test',
            ],
        ]);
    }
}
