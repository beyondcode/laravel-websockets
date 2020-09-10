<?php

namespace BeyondCode\LaravelWebSockets\Test;

use BeyondCode\LaravelWebSockets\Server\Exceptions\InvalidSignature;

class PresenceChannelTest extends TestCase
{
    public function test_connect_to_presence_channel_with_invalid_signature()
    {
        $this->expectException(InvalidSignature::class);

        $connection = $this->newConnection();

        $message = new Mocks\Message([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => 'invalid',
                'channel' => 'presence-channel',
            ],
        ]);

        $this->pusherServer->onOpen($connection);
        $this->pusherServer->onMessage($connection, $message);
    }

    public function test_connect_to_presence_channel_with_valid_signature()
    {
        $connection = $this->newConnection();

        $this->pusherServer->onOpen($connection);

        $user = [
            'user_id' => 1,
            'user_info' => [
                'name' => 'Rick',
            ],
        ];

        $encodedUser = json_encode($user);

        $signature = "{$connection->socketId}:presence-channel:".$encodedUser;
        $hashedAppSecret = hash_hmac('sha256', $signature, $connection->app->secret);

        $message = new Mocks\Message([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => "{$connection->app->key}:{$hashedAppSecret}",
                'channel' => 'presence-channel',
                'channel_data' => json_encode($user),
            ],
        ]);

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'presence-channel',
        ]);

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'presence-channel')
            ->then(function ($total) {
                $this->assertEquals(1, $total);
            });
    }

    public function test_presence_channel_broadcast_member_events()
    {
        $rick = $this->newPresenceConnection('presence-channel', ['user_id' => 1]);
        $morty = $this->newPresenceConnection('presence-channel', ['user_id' => 2]);

        $rick->assertSentEvent('pusher_internal:member_added', [
            'channel' => 'presence-channel',
            'data' => json_encode(['user_id' => 2]),
        ]);

        $this->channelManager
            ->getChannelMembers('1234', 'presence-channel')
            ->then(function ($members) {
                $this->assertCount(2, $members);
            });

        $this->pusherServer->onClose($morty);

        $rick->assertSentEvent('pusher_internal:member_removed', [
            'channel' => 'presence-channel',
            'data' => json_encode(['user_id' => 2]),
        ]);

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'presence-channel')
            ->then(function ($total) {
                $this->assertEquals(1, $total);
            });

        $this->channelManager
            ->getChannelMembers('1234', 'presence-channel')
            ->then(function ($members) {
                $this->assertCount(1, $members);
                $this->assertEquals(1, $members[0]->user_id);
            });
    }

    public function test_unsubscribe_from_presence_channel()
    {
        $connection = $this->newPresenceConnection('presence-channel', ['user_id' => 1]);

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'presence-channel')
            ->then(function ($total) {
                $this->assertEquals(1, $total);
            });

        $message = new Mocks\Message([
            'event' => 'pusher:unsubscribe',
            'data' => [
                'channel' => 'presence-channel',
            ],
        ]);

        $this->pusherServer->onMessage($connection, $message);

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'presence-channel')
            ->then(function ($total) {
                $this->assertEquals(0, $total);
            });
    }

    public function test_can_whisper_to_private_channel()
    {
        $this->app['config']->set('websockets.apps.0.enable_client_messages', true);

        $rick = $this->newPresenceConnection('presence-channel', ['user_id' => 1]);
        $morty = $this->newPresenceConnection('presence-channel', ['user_id' => 2]);

        $message = new Mocks\Message([
            'event' => 'client-test-whisper',
            'data' => [],
            'channel' => 'presence-channel',
        ]);

        $this->pusherServer->onMessage($rick, $message);

        $rick->assertNotSentEvent('client-test-whisper');
        $morty->assertSentEvent('client-test-whisper', ['data' => [], 'channel' => 'presence-channel']);
    }

    public function test_cannot_whisper_to_public_channel_if_having_whispering_disabled()
    {
        $rick = $this->newPresenceConnection('presence-channel', ['user_id' => 1]);
        $morty = $this->newPresenceConnection('presence-channel', ['user_id' => 2]);

        $message = new Mocks\Message([
            'event' => 'client-test-whisper',
            'data' => [],
            'channel' => 'presence-channel',
        ]);

        $this->pusherServer->onMessage($rick, $message);

        $rick->assertNotSentEvent('client-test-whisper');
        $morty->assertNotSentEvent('client-test-whisper');
    }

    public function test_statistics_get_collected_for_presenece_channels()
    {
        $rick = $this->newPresenceConnection('presence-channel', ['user_id' => 1]);
        $morty = $this->newPresenceConnection('presence-channel', ['user_id' => 2]);

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
