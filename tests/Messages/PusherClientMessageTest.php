<?php

namespace BeyondCode\LaravelWebSockets\Tests\Messages;

use Illuminate\Support\Facades\Event;
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
                'client-event' => 'test',
            ],
        ]));

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

        $message = new Message(json_encode([
            'event' => 'client-test',
            'channel' => 'test-channel',
            'data' => [
                'client-event' => 'test',
            ],
        ]));

        $this->pusherServer->onMessage($connection1, $message);

        $connection1->assertNotSentEvent('client-test');

        $connection2->assertSentEvent('client-test', [
            'data' => [
                'client-event' => 'test',
            ],
        ]);
    }

    /** @test */
    public function client_messages_dispatch_only_specified_wildcard_events_when_enabled()
    {
        Event::fake();

        $this->app['config']->set('websockets.apps', [
            [
                'name' => 'Test App',
                'id' => 1234,
                'key' => 'TestKey',
                'secret' => 'TestSecret',
                'enable_client_messages' => true,
                'dispatch_events_for_client_messages' => ['client-test'],
                'enable_statistics' => true,
            ],
        ]);

        $connection = $this->getConnectedWebSocketConnection(['test-channel']);

        $message1 = new Message(json_encode([
            'event' => 'client-test',
            'channel' => 'test-channel',
            'data' => [
                'client-event' => 'test',
            ],
        ]));
        $message2 = new Message(json_encode([
            'event' => 'client-test2',
            'channel' => 'test-channel',
            'data' => [
                'client-event' => 'other test',
            ],
        ]));

        $this->pusherServer->onMessage($connection, $message1);
        $this->pusherServer->onMessage($connection, $message2);

        Event::assertDispatched('websockets.client-test');
        Event::assertNotDispatched('websockets.client-test2');
    }

    /** @test */
    public function client_messages_do_not_dispatch_wildcard_events_when_client_messages_are_disabled()
    {
        Event::fake();

        $this->app['config']->set('websockets.apps', [
            [
                'name' => 'Test App',
                'id' => 1234,
                'key' => 'TestKey',
                'secret' => 'TestSecret',
                'enable_client_messages' => false,
                'dispatch_events_for_client_messages' => ['client-test'],
                'enable_statistics' => true,
            ],
        ]);

        $connection = $this->getConnectedWebSocketConnection(['test-channel']);

        $message = new Message(json_encode([
            'event' => 'client-test',
            'channel' => 'test-channel',
            'data' => [
                'client-event' => 'test',
            ],
        ]));

        $this->pusherServer->onMessage($connection, $message);

        Event::assertNotDispatched('websockets.client-test');
    }
}
