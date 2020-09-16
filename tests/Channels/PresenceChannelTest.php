<?php

namespace BeyondCode\LaravelWebSockets\Tests\Channels;

use BeyondCode\LaravelWebSockets\Tests\Mocks\Connection;
use BeyondCode\LaravelWebSockets\Tests\Mocks\Message;
use BeyondCode\LaravelWebSockets\Tests\TestCase;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\InvalidSignature;

class PresenceChannelTest extends TestCase
{
    /** @test */
    public function clients_need_valid_auth_signatures_to_join_presence_channels()
    {
        $this->expectException(InvalidSignature::class);

        $connection = $this->getWebSocketConnection();

        $message = new Message(json_encode([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => 'invalid',
                'channel' => 'presence-channel',
            ],
        ]));

        $this->pusherServer->onOpen($connection);

        $this->pusherServer->onMessage($connection, $message);
    }

    /** @test */
    public function clients_with_valid_auth_signatures_can_join_presence_channels()
    {
        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        $channelData = [
            'user_id' => 1,
            'user_info' => [
                'name' => 'Marcel',
            ],
        ];

        $message = $this->getSignedSubscribeMessage($connection, 'presence-channel', $channelData);

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'presence-channel',
        ]);
    }

    /** @test */
    public function clients_with_no_user_info_can_join_presence_channels()
    {
        $connection = $this->getWebSocketConnection();

        $this->pusherServer->onOpen($connection);

        $channelData = [
            'user_id' => 1,
        ];

        $message = $this->getSignedSubscribeMessage($connection, 'presence-channel', $channelData);

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'presence-channel',
        ]);
    }

    /** @test */
    public function multiple_clients_with_same_user_id_are_counted_once()
    {
        $this->pusherServer->onOpen($connection = $this->getWebSocketConnection());
        $this->pusherServer->onOpen($connection2 = $this->getWebSocketConnection());

        $channelName = 'presence-channel';
        $channelData = [
            'user_id' => $userId = 'user:1',
        ];

        $this->pusherServer->onMessage($connection, $this->getSignedSubscribeMessage($connection, $channelName, $channelData));
        $this->pusherServer->onMessage($connection2, $this->getSignedSubscribeMessage($connection2, $channelName, $channelData));

        $connection2->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => $channelName,
            'data' => json_encode([
                'presence' => [
                    'ids' => [$userId],
                    'hash' => [
                        $userId => [],
                    ],
                    'count' => 1,
                ],
            ]),
        ]);
    }

    /** @test */
    public function multiple_clients_with_same_user_id_trigger_member_added_and_removed_event_only_on_first_and_last_socket_connection()
    {
        $channelName = 'presence-channel';

        // Connect the `observer` user to the server
        $this->pusherServer->onOpen($observerConnection = $this->getWebSocketConnection());
        $this->pusherServer->onMessage($observerConnection, $this->getSignedSubscribeMessage($observerConnection, $channelName, ['user_id' => 'observer']));

        // Connect the first socket for user `user:1` to the server
        $this->pusherServer->onOpen($firstConnection = $this->getWebSocketConnection());
        $this->pusherServer->onMessage($firstConnection, $this->getSignedSubscribeMessage($firstConnection, $channelName, ['user_id' => 'user:1']));

        // Make sure the observer sees a `member_added` event for `user:1`
        $observerConnection->assertSentEvent('pusher_internal:member_added');
        $observerConnection->resetEvents();

        // Connect the second socket for user `user:1` to the server
        $this->pusherServer->onOpen($secondConnection = $this->getWebSocketConnection());
        $this->pusherServer->onMessage($secondConnection, $this->getSignedSubscribeMessage($secondConnection, $channelName, ['user_id' => 'user:1']));

        // Make sure the observer was not notified of a `member_added` event (user was already connected)
        $observerConnection->assertNotSentEvent('pusher_internal:member_added');

        // Disconnect the first socket for user `user:1` on the server
        $this->pusherServer->onClose($firstConnection);

        // Make sure the observer was not notified of a `member_removed` event (user still connected on another socket)
        $observerConnection->assertNotSentEvent('pusher_internal:member_removed');

        // Disconnect the second (and last) socket for user `user:1` on the server
        $this->pusherServer->onClose($secondConnection);

        // Make sure the observer was notified of a `member_removed` event (last socket for user was disconnected)
        $observerConnection->assertSentEvent('pusher_internal:member_removed');
    }

    private function getSignedSubscribeMessage(Connection $connection, string $channelName, array $channelData): Message
    {
        $signature = "{$connection->socketId}:{$channelName}:".json_encode($channelData);

        return new Message(json_encode([
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => $connection->app->key.':'.hash_hmac('sha256', $signature, $connection->app->secret),
                'channel' => $channelName,
                'channel_data' => json_encode($channelData),
            ],
        ]));
    }
}
