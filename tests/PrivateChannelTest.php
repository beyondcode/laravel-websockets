<?php

namespace BeyondCode\LaravelWebSockets\Test;

use BeyondCode\LaravelWebSockets\Server\Exceptions\InvalidSignature;

class PrivateChannelTest extends TestCase
{
    public function test_connect_to_private_channel_with_invalid_signature()
    {
        $this->expectException(InvalidSignature::class);

        $connection = $this->newConnection();

        $message = new Mocks\Message([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => 'invalid',
                'channel' => 'private-channel',
            ],
        ]);

        $this->pusherServer->onOpen($connection);
        $this->pusherServer->onMessage($connection, $message);
    }

    public function test_connect_to_private_channel_with_valid_signature()
    {
        $connection = $this->newConnection();

        $this->pusherServer->onOpen($connection);

        $signature = "{$connection->socketId}:private-channel";
        $hashedAppSecret = hash_hmac('sha256', $signature, $connection->app->secret);

        $message = new Mocks\Message([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => "{$connection->app->key}:{$hashedAppSecret}",
                'channel' => 'private-channel',
            ],
        ]);

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'private-channel',
        ]);

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'private-channel')
            ->then(function ($total) {
                $this->assertEquals(1, $total);
            });
    }

    public function test_unsubscribe_from_private_channel()
    {
        $connection = $this->newPrivateConnection('private-channel');

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'private-channel')
            ->then(function ($total) {
                $this->assertEquals(1, $total);
            });

        $message = new Mocks\Message([
            'event' => 'pusher:unsubscribe',
            'data' => [
                'channel' => 'private-channel',
            ],
        ]);

        $this->pusherServer->onMessage($connection, $message);

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'private-channel')
            ->then(function ($total) {
                $this->assertEquals(0, $total);
            });
    }

    public function test_can_whisper_to_private_channel()
    {
        $this->app['config']->set('websockets.apps.0.enable_client_messages', true);

        $rick = $this->newPrivateConnection('private-channel');
        $morty = $this->newPrivateConnection('private-channel');

        $message = new Mocks\Message([
            'event' => 'client-test-whisper',
            'data' => [],
            'channel' => 'private-channel',
        ]);

        $this->pusherServer->onMessage($rick, $message);

        $rick->assertNotSentEvent('client-test-whisper');
        $morty->assertSentEvent('client-test-whisper', ['data' => [], 'channel' => 'private-channel']);
    }

    public function test_cannot_whisper_to_public_channel_if_having_whispering_disabled()
    {
        $rick = $this->newPrivateConnection('private-channel');
        $morty = $this->newPrivateConnection('private-channel');

        $message = new Mocks\Message([
            'event' => 'client-test-whisper',
            'data' => [],
            'channel' => 'private-channel',
        ]);

        $this->pusherServer->onMessage($rick, $message);

        $rick->assertNotSentEvent('client-test-whisper');
        $morty->assertNotSentEvent('client-test-whisper');
    }

    public function test_statistics_get_collected_for_private_channels()
    {
        $rick = $this->newPrivateConnection('private-channel');
        $morty = $this->newPrivateConnection('private-channel');

        $this->statisticsCollector
            ->getStatistics()
            ->then(function ($statistics) {
                $this->assertCount(1, $statistics);
            });

        $this->statisticsCollector
            ->getAppStatistics('1234')
            ->then(function ($statistic) {
                $this->assertEquals([
                    'peak_connections_count' => 2,
                    'websocket_messages_count' => 2,
                    'api_messages_count' => 0,
                    'app_id' => '1234',
                ], $statistic->toArray());
            });
    }
}
