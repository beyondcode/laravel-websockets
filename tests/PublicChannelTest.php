<?php

namespace BeyondCode\LaravelWebSockets\Test;

class PublicChannelTest extends TestCase
{
    public function test_connect_to_public_channel()
    {
        $connection = $this->newActiveConnection(['public-channel']);

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'public-channel')
            ->then(function ($total) {
                $this->assertEquals(1, $total);
            });

        $connection->assertSentEvent(
            'pusher:connection_established',
            [
                'data' => json_encode([
                    'socket_id' => $connection->socketId,
                    'activity_timeout' => 30,
                ]),
            ],
        );

        $connection->assertSentEvent(
            'pusher_internal:subscription_succeeded',
            ['channel' => 'public-channel']
        );
    }

    public function test_unsubscribe_from_public_channel()
    {
        $connection = $this->newActiveConnection(['public-channel']);

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'public-channel')
            ->then(function ($total) {
                $this->assertEquals(1, $total);
            });

        $message = new Mocks\Message([
            'event' => 'pusher:unsubscribe',
            'data' => [
                'channel' => 'public-channel',
            ],
        ]);

        $this->pusherServer->onMessage($connection, $message);

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'public-channel')
            ->then(function ($total) {
                $this->assertEquals(0, $total);
            });
    }

    public function test_can_whisper_to_public_channel()
    {
        $this->app['config']->set('websockets.apps.0.enable_client_messages', true);

        $rick = $this->newActiveConnection(['public-channel']);
        $morty = $this->newActiveConnection(['public-channel']);

        $message = new Mocks\Message([
            'event' => 'client-test-whisper',
            'data' => [],
            'channel' => 'public-channel',
        ]);

        $this->pusherServer->onMessage($rick, $message);

        $rick->assertNotSentEvent('client-test-whisper');
        $morty->assertSentEvent('client-test-whisper', ['data' => [], 'channel' => 'public-channel']);
    }

    public function test_cannot_whisper_to_public_channel_if_having_whispering_disabled()
    {
        $rick = $this->newActiveConnection(['public-channel']);
        $morty = $this->newActiveConnection(['public-channel']);

        $message = new Mocks\Message([
            'event' => 'client-test-whisper',
            'data' => [],
            'channel' => 'public-channel',
        ]);

        $this->pusherServer->onMessage($rick, $message);

        $rick->assertNotSentEvent('client-test-whisper');
        $morty->assertNotSentEvent('client-test-whisper');
    }

    public function test_statistics_get_collected_for_public_channels()
    {
        $rick = $this->newActiveConnection(['public-channel']);
        $morty = $this->newActiveConnection(['public-channel']);

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
