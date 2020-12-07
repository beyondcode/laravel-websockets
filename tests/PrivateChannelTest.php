<?php

namespace BeyondCode\LaravelWebSockets\Test;

use BeyondCode\LaravelWebSockets\API\TriggerEvent;
use BeyondCode\LaravelWebSockets\Server\Exceptions\InvalidSignature;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\JsonResponse;
use Pusher\Pusher;
use Ratchet\ConnectionInterface;

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

        $message = new Mocks\SignedMessage([
            'event' => 'pusher:subscribe',
            'data' => [
                'channel' => 'private-channel',
            ],
        ], $connection, 'private-channel');

        $this->pusherServer->onMessage($connection, $message);

        $connection->assertSentEvent('pusher_internal:subscription_succeeded', [
            'channel' => 'private-channel',
        ]);

        $this->channelManager->getGlobalConnectionsCount('1234', 'private-channel')->then(function ($total) {
            $this->assertEquals(1, $total);
        });
    }

    public function test_unsubscribe_from_private_channel()
    {
        $connection = $this->newPrivateConnection('private-channel');

        $this->channelManager->getGlobalConnectionsCount('1234', 'private-channel')->then(function ($total) {
            $this->assertEquals(1, $total);
        });

        $message = new Mocks\Message([
            'event' => 'pusher:unsubscribe',
            'data' => [
                'channel' => 'private-channel',
            ],
        ]);

        $this->pusherServer->onMessage($connection, $message);

        $this->channelManager->getGlobalConnectionsCount('1234', 'private-channel')->then(function ($total) {
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

        $this->statisticsCollector->getStatistics()->then(function ($statistics) {
            $this->assertCount(1, $statistics);
        });

        $this->statisticsCollector->getAppStatistics('1234')->then(function ($statistic) {
            $this->assertEquals([
                'peak_connections_count' => 2,
                'websocket_messages_count' => 2,
                'api_messages_count' => 0,
                'app_id' => '1234',
            ], $statistic->toArray());
        });
    }

    public function test_local_connections_for_private_channels()
    {
        $this->newPrivateConnection('private-channel');
        $this->newPrivateConnection('private-channel-2');

        $this->channelManager->getLocalConnections()->then(function ($connections) {
            $this->assertCount(2, $connections);

            foreach ($connections as $connection) {
                $this->assertInstanceOf(
                    ConnectionInterface::class, $connection
                );
            }
        });
    }

    public function test_events_are_processed_by_on_message_on_private_channels()
    {
        $this->runOnlyOnRedisReplication();

        $connection = $this->newPrivateConnection('private-channel');

        $message = new Mocks\SignedMessage([
            'appId' => '1234',
            'serverId' => 'different_server_id',
            'event' => 'some-event',
            'data' => [
                'channel' => 'private-channel',
                'test' => 'yes',
            ],
        ], $connection, 'private-channel');

        $this->channelManager->onMessage(
            $this->channelManager->getRedisKey('1234', 'private-channel'),
            $message->getPayload()
        );

        // The message does not contain appId and serverId anymore.
        $message = new Mocks\SignedMessage([
            'event' => 'some-event',
            'data' => [
                'channel' => 'private-channel',
                'test' => 'yes',
            ],
        ], $connection, 'private-channel');

        $connection->assertSentEvent('some-event', $message->getPayloadAsArray());
    }

    public function test_events_get_replicated_across_connections_for_private_channels()
    {
        $this->runOnlyOnRedisReplication();

        $connection = $this->newPrivateConnection('private-channel');
        $receiver = $this->newPrivateConnection('private-channel');

        $message = new Mocks\SignedMessage([
            'appId' => '1234',
            'serverId' => $this->channelManager->getServerId(),
            'event' => 'some-event',
            'data' => [
                'channel' => 'private-channel',
                'test' => 'yes',
            ],
            'socketId' => $connection->socketId,
        ], $connection, 'private-channel');

        $channel = $this->channelManager->find('1234', 'private-channel');

        $channel->broadcastToEveryoneExcept(
            $message->getPayloadAsObject(), $connection->socketId, '1234', true
        );

        $receiver->assertSentEvent('some-event', $message->getPayloadAsArray());

        $this->getSubscribeClient()
            ->assertNothingDispatched();

        $this->getPublishClient()->assertCalledWithArgs('publish', [
            $this->channelManager->getRedisKey('1234', 'private-channel'),
            $message->getPayload(),
        ]);
    }

    public function test_it_fires_the_event_to_private_channel()
    {
        $this->newPrivateConnection('private-channel');

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/events';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'POST', $requestPath, [
                'name' => 'some-event',
                'channels' => ['private-channel'],
                'data' => json_encode(['some-data' => 'yes']),
            ],
        );

        $request = new Request('POST', "{$requestPath}?{$queryString}&".http_build_query($routeParams));

        $controller = app(TriggerEvent::class);

        $controller->onOpen($connection, $request);

        /** @var JsonResponse $response */
        $response = array_pop($connection->sentRawData);

        $this->assertSame([], json_decode($response->getContent(), true));

        $this->statisticsCollector->getAppStatistics('1234')->then(function ($statistic) {
            $this->assertEquals([
                'peak_connections_count' => 1,
                'websocket_messages_count' => 1,
                'api_messages_count' => 1,
                'app_id' => '1234',
            ], $statistic->toArray());
        });
    }

    public function test_it_fires_event_across_servers_when_there_are_not_users_locally_for_private_channel()
    {
        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/events';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'POST', $requestPath, [
                'name' => 'some-event',
                'channels' => ['private-channel'],
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
            $this->channelManager->getPublishClient()->assertCalledWithArgsCount(1, 'publish', [
                $this->channelManager->getRedisKey('1234', 'private-channel'),
                json_encode([
                    'event' => 'some-event',
                    'channel' => 'private-channel',
                    'data' => json_encode(['some-data' => 'yes']),
                    'appId' => '1234',
                    'socketId' => null,
                    'serverId' => $this->channelManager->getServerId(),
                ]),
            ]);
        }
    }

    public function test_it_fires_event_across_servers_when_there_are_users_locally_for_private_channel()
    {
        $wsConnection = $this->newPrivateConnection('private-channel');

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/events';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'POST', $requestPath, [
                'name' => 'some-event',
                'channels' => ['private-channel'],
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
            $this->channelManager->getPublishClient()->assertCalledWithArgsCount(1, 'publish', [
                $this->channelManager->getRedisKey('1234', 'private-channel'),
                json_encode([
                    'event' => 'some-event',
                    'channel' => 'private-channel',
                    'data' => json_encode(['some-data' => 'yes']),
                    'appId' => '1234',
                    'socketId' => null,
                    'serverId' => $this->channelManager->getServerId(),
                ]),
            ]);
        }

        $wsConnection->assertSentEvent('some-event', [
            'channel' => 'private-channel',
            'data' => json_encode(['some-data' => 'yes']),
        ]);
    }
}
