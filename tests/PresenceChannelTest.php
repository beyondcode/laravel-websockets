<?php

namespace BeyondCode\LaravelWebSockets\Test;

use BeyondCode\LaravelWebSockets\API\TriggerEvent;
use BeyondCode\LaravelWebSockets\Server\Exceptions\InvalidSignature;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\JsonResponse;
use Pusher\Pusher;
use Ratchet\ConnectionInterface;

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

        $message = new Mocks\SignedMessage([
            'event' => 'pusher:subscribe',
            'data' => [
                'channel' => 'presence-channel',
                'channel_data' => $encodedUser,
            ],
        ], $connection, 'presence-channel', $encodedUser);

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

    public function test_connect_to_presence_channel_when_user_with_same_ids_is_already_joined()
    {
        $rick = $this->newPresenceConnection('presence-channel', ['user_id' => 1]);
        $morty = $this->newPresenceConnection('presence-channel', ['user_id' => 2]);
        $pickleRick = $this->newPresenceConnection('presence-channel', ['user_id' => 1]);

        foreach ([$rick, $morty, $pickleRick] as $connection) {
            $connection->assertSentEvent('pusher_internal:subscription_succeeded', [
                'channel' => 'presence-channel',
            ]);
        }

        $rick->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'presence-channel',
            'data' => json_encode([
                'presence' => [
                    'ids' => ['1'],
                    'hash' => ['1' => []],
                    'count' => 1,
                ],
            ]),
        ]);

        $morty->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'presence-channel',
            'data' => json_encode([
                'presence' => [
                    'ids' => ['1', '2'],
                    'hash' => ['1' => [], '2' => []],
                    'count' => 2,
                ],
            ]),
        ]);

        // The duplicated-user_id connection should get basically the list of ids
        // without dealing with duplicate user ids.
        $pickleRick->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'presence-channel',
            'data' => json_encode([
                'presence' => [
                    'ids' => ['1', '2'],
                    'hash' => ['1' => [], '2' => []],
                    'count' => 2,
                ],
            ]),
        ]);

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'presence-channel')
            ->then(function ($total) {
                $this->assertEquals(3, $total);
            });

        $this->channelManager
            ->getChannelMembers('1234', 'presence-channel')
            ->then(function ($members) {
                $this->assertCount(2, $members);
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
            ->then(function ($members) use ($rick) {
                $this->assertCount(1, $members);
                $this->assertEquals(1, $members[$rick->socketId]->user_id);
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

    public function test_local_connections_for_presence_channels()
    {
        $this->newPresenceConnection('presence-channel', ['user_id' => 1]);
        $this->newPresenceConnection('presence-channel-2', ['user_id' => 2]);

        $this->channelManager
            ->getLocalConnections()
            ->then(function ($connections) {
                $this->assertCount(2, $connections);

                foreach ($connections as $connection) {
                    $this->assertInstanceOf(
                        ConnectionInterface::class, $connection
                    );
                }
            });
    }

    public function test_multiple_clients_with_same_user_id_trigger_member_added_and_removed_event_only_on_first_and_last_socket_connection()
    {
        // Connect the `observer` user to the server
        $observerConnection = $this->newPresenceConnection('presence-channel', ['user_id' => 'observer']);

        // Connect the first socket for user `1` to the server
        $firstConnection = $this->newPresenceConnection('presence-channel', ['user_id' => '1']);

        // Make sure the observer sees a `member_added` event for `user:1`
        $observerConnection->assertSentEvent('pusher_internal:member_added', [
            'event' => 'pusher_internal:member_added',
            'channel' => 'presence-channel',
            'data' => json_encode(['user_id' => '1']),
        ])->resetEvents();

        // Connect the second socket for user `1` to the server
        $secondConnection = $this->newPresenceConnection('presence-channel', ['user_id' => '1']);

        // Make sure the observer was not notified of a `member_added` event (user was already connected)
        $observerConnection->assertNotSentEvent('pusher_internal:member_added');

        // Disconnect the first socket for user `1` on the server
        $this->pusherServer->onClose($firstConnection);

        // Make sure the observer was not notified of a `member_removed` event (user still connected on another socket)
        $observerConnection->assertNotSentEvent('pusher_internal:member_removed');

        // Disconnect the second (and last) socket for user `1` on the server
        $this->pusherServer->onClose($secondConnection);

        // Make sure the observer was notified of a `member_removed` event (last socket for user was disconnected)
        $observerConnection->assertSentEvent('pusher_internal:member_removed');

        $this->channelManager
            ->getMemberSockets('1', '1234', 'presence-channel')
            ->then(function ($sockets) {
                $this->assertCount(0, $sockets);
            });

        $this->channelManager
            ->getMemberSockets('2', '1234', 'presence-channel')
            ->then(function ($sockets) {
                $this->assertCount(0, $sockets);
            });

        $this->channelManager
            ->getMemberSockets('observer', '1234', 'presence-channel')
            ->then(function ($sockets) {
                $this->assertCount(1, $sockets);
            });
    }

    public function test_events_are_processed_by_on_message_on_presence_channels()
    {
        $this->runOnlyOnRedisReplication();

        $user = [
            'user_id' => 1,
            'user_info' => [
                'name' => 'Rick',
            ],
        ];

        $connection = $this->newPresenceConnection('presence-channel', $user);

        $encodedUser = json_encode($user);

        $message = new Mocks\SignedMessage([
            'appId' => '1234',
            'serverId' => 'different_server_id',
            'event' => 'some-event',
            'data' => [
                'channel' => 'presence-channel',
                'channel_data' => $encodedUser,
                'test' => 'yes',
            ],
        ], $connection, 'presence-channel', $encodedUser);

        $this->channelManager->onMessage(
            $this->channelManager->getRedisKey('1234', 'presence-channel'),
            $message->getPayload()
        );

        // The message does not contain appId and serverId anymore.
        $message = new Mocks\SignedMessage([
            'event' => 'some-event',
            'data' => [
                'channel' => 'presence-channel',
                'channel_data' => $encodedUser,
                'test' => 'yes',
            ],
        ], $connection, 'presence-channel', $encodedUser);

        $connection->assertSentEvent('some-event', $message->getPayloadAsArray());
    }

    public function test_events_get_replicated_across_connections_for_presence_channels()
    {
        $this->runOnlyOnRedisReplication();

        $connection = $this->newPresenceConnection('presence-channel');
        $receiver = $this->newPresenceConnection('presence-channel', ['user_id' => 2]);

        $user = [
            'user_id' => 1,
            'user_info' => [
                'name' => 'Rick',
            ],
        ];

        $encodedUser = json_encode($user);

        $message = new Mocks\SignedMessage([
            'appId' => '1234',
            'serverId' => $this->channelManager->getServerId(),
            'event' => 'some-event',
            'data' => [
                'channel' => 'presence-channel',
                'channel_data' => $encodedUser,
                'test' => 'yes',
            ],
            'socketId' => $connection->socketId,
        ], $connection, 'presence-channel', $encodedUser);

        $channel = $this->channelManager->find('1234', 'presence-channel');

        $channel->broadcastToEveryoneExcept(
            $message->getPayloadAsObject(), $connection->socketId, '1234', true
        );

        $receiver->assertSentEvent('some-event', $message->getPayloadAsArray());

        $this->getSubscribeClient()
            ->assertNothingDispatched();

        $this->getPublishClient()
            ->assertCalledWithArgs('publish', [
                $this->channelManager->getRedisKey('1234', 'presence-channel'),
                $message->getPayload(),
            ]);
    }

    public function test_it_fires_the_event_to_presence_channel()
    {
        $this->newPresenceConnection('presence-channel');

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/events';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'POST', $requestPath, [
                'name' => 'some-event',
                'channels' => ['presence-channel'],
                'data' => json_encode(['some-data' => 'yes']),
            ],
        );

        $request = new Request('POST', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(TriggerEvent::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([], json_decode($response->getContent(), true));

        $this->statisticsCollector
            ->getAppStatistics('1234')
            ->then(function ($statistic) {
                $this->assertEquals([
                    'peak_connections_count' => 1,
                    'websocket_messages_count' => 1,
                    'api_messages_count' => 1,
                    'app_id' => '1234',
                ], $statistic->toArray());
            });
    }

    public function test_it_fires_event_across_servers_when_there_are_not_users_locally_for_presence_channel()
    {
        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/events';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'POST', $requestPath, [
                'name' => 'some-event',
                'channels' => ['presence-channel'],
                'data' => json_encode(['some-data' => 'yes']),
            ],
        );

        $request = new Request('POST', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(TriggerEvent::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([], json_decode($response->getContent(), true));

        if (method_exists($this->channelManager, 'getPublishClient')) {
            $this->channelManager
                ->getPublishClient()
                ->assertCalledWithArgsCount(1, 'publish', [
                    $this->channelManager->getRedisKey('1234', 'presence-channel'),
                    json_encode([
                        'event' => 'some-event',
                        'channel' => 'presence-channel',
                        'data' => json_encode(['some-data' => 'yes']),
                        'appId' => '1234',
                        'socketId' => null,
                        'serverId' => $this->channelManager->getServerId(),
                    ]),
                ]);
        }
    }

    public function test_it_fires_event_across_servers_when_there_are_users_locally_for_presence_channel()
    {
        $wsConnection = $this->newPresenceConnection('presence-channel');

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/events';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'POST', $requestPath, [
                'name' => 'some-event',
                'channels' => ['presence-channel'],
                'data' => json_encode(['some-data' => 'yes']),
            ],
        );

        $request = new Request('POST', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(TriggerEvent::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([], json_decode($response->getContent(), true));

        if (method_exists($this->channelManager, 'getPublishClient')) {
            $this->channelManager
                ->getPublishClient()
                ->assertCalledWithArgsCount(1, 'publish', [
                    $this->channelManager->getRedisKey('1234', 'presence-channel'),
                    json_encode([
                        'event' => 'some-event',
                        'channel' => 'presence-channel',
                        'data' => json_encode(['some-data' => 'yes']),
                        'appId' => '1234',
                        'socketId' => null,
                        'serverId' => $this->channelManager->getServerId(),
                    ]),
                ]);
        }

        $wsConnection->assertSentEvent('some-event', [
            'channel' => 'presence-channel',
            'data' => json_encode(['some-data' => 'yes']),
        ]);
    }
}
