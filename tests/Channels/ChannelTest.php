<?php

namespace BeyondCode\LaravelWebsockets\Tests\Channels;

use BeyondCode\LaravelWebSockets\Tests\TestCase;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Message;

class ChannelTest extends TestCase
{
    /** @test */
    public function clients_can_subscribe_to_channels()
    {
        $connection = $this->getWebSocketConnection();

        $message = new Message(json_encode([
            'event' => 'pusher:subscribe',
            'data' => [
                'channel' => 'basic-channel',
            ],
        ]));

        $this->pusherServer->onOpen($connection);

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'basic-channel',
        ]);
    }

    /** @test */
    public function clients_can_unsubscribe_from_channels()
    {
        $connection = $this->getConnectedWebSocketConnection(['test-channel']);

        $channel = $this->getChannel($connection, 'test-channel');

        $this->assertTrue($channel->hasConnections());

        $message = new Message(json_encode([
            'event' => 'pusher:unsubscribe',
            'data' => [
                'channel' => 'test-channel',
            ],
        ]));

        $this->pusherServer->onMessage($connection, $message);

        $this->assertFalse($channel->hasConnections());
    }

    /** @test */
    public function a_client_cannot_broadcast_to_other_clients_by_default()
    {
        // One connection inside channel "test-channel".
        $existingConnection = $this->getConnectedWebSocketConnection(['test-channel']);

        $connection = $this->getConnectedWebSocketConnection(['test-channel']);

        $message = new Message('{"event": "client-test", "data": {}, "channel": "test-channel"}');

        $this->pusherServer->onMessage($connection, $message);

        $existingConnection->assertNotSentEvent('client-test');
    }

    /** @test */
    public function a_client_can_be_enabled_to_broadcast_to_other_clients()
    {
        config()->set('websockets.apps.0.enable_client_messages', true);

        // One connection inside channel "test-channel".
        $existingConnection = $this->getConnectedWebSocketConnection(['test-channel']);

        $connection = $this->getConnectedWebSocketConnection(['test-channel']);

        $message = new Message('{"event": "client-test", "data": {}, "channel": "test-channel"}');

        $this->pusherServer->onMessage($connection, $message);

        $existingConnection->assertSentEvent('client-test');
    }

    /** @test */
    public function closed_connections_get_removed_from_all_connected_channels()
    {
        $connection = $this->getConnectedWebSocketConnection(['test-channel-1', 'test-channel-2']);

        $channel1 = $this->getChannel($connection, 'test-channel-1');
        $channel2 = $this->getChannel($connection, 'test-channel-2');

        $this->assertTrue($channel1->hasConnections());
        $this->assertTrue($channel2->hasConnections());

        $this->pusherServer->onClose($connection);

        $this->assertFalse($channel1->hasConnections());
        $this->assertFalse($channel2->hasConnections());
    }

    /** @test */
    public function channels_can_broadcast_messages_to_all_connections()
    {
        $connection1 = $this->getConnectedWebSocketConnection(['test-channel']);
        $connection2 = $this->getConnectedWebSocketConnection(['test-channel']);

        $channel = $this->getChannel($connection1, 'test-channel');

        $channel->broadcast([
            'event' => 'broadcasted-event',
            'channel' => 'test-channel',
        ]);

        $connection1->assertSentEvent('broadcasted-event');
        $connection2->assertSentEvent('broadcasted-event');
    }

    /** @test */
    public function channels_can_broadcast_messages_to_all_connections_except_the_given_connection()
    {
        $connection1 = $this->getConnectedWebSocketConnection(['test-channel']);
        $connection2 = $this->getConnectedWebSocketConnection(['test-channel']);

        $channel = $this->getChannel($connection1, 'test-channel');

        $channel->broadcastToOthers($connection1, [
            'event' => 'broadcasted-event',
            'channel' => 'test-channel',
        ]);

        $connection1->assertNotSentEvent('broadcasted-event');
        $connection2->assertSentEvent('broadcasted-event');
    }

    /** @test */
    public function it_responds_correctly_to_the_ping_message()
    {
        $connection = $this->getConnectedWebSocketConnection();

        $message = new Message(json_encode([
            'event' => 'pusher:ping',
        ]));

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher:pong');
    }
}
