<?php

namespace BeyondCode\LaravelWebSockets\Test;

use BeyondCode\LaravelWebSockets\API\TriggerEvent;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\JsonResponse;
use Pusher\Pusher;
use Ratchet\ConnectionInterface;

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

    public function test_local_connections_for_public_channels()
    {
        $this->newActiveConnection(['public-channel']);
        $this->newActiveConnection(['public-channel-2']);

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

    public function test_events_are_processed_by_on_message_on_public_channels()
    {
        $this->runOnlyOnRedisReplication();

        $connection = $this->newActiveConnection(['public-channel']);

        $message = new Mocks\Message([
            'appId' => '1234',
            'serverId' => 'different_server_id',
            'event' => 'some-event',
            'data' => [
                'channel' => 'public-channel',
                'test' => 'yes',
            ],
        ]);

        $this->channelManager->onMessage(
            $this->channelManager->getRedisKey('1234', 'public-channel'),
            $message->getPayload()
        );

        // The message does not contain appId and serverId anymore.
        $message = new Mocks\Message([
            'event' => 'some-event',
            'data' => [
                'channel' => 'public-channel',
                'test' => 'yes',
            ],
        ]);

        $connection->assertSentEvent('some-event', $message->getPayloadAsArray());
    }

    public function test_events_get_replicated_across_connections_for_public_channels()
    {
        $this->runOnlyOnRedisReplication();

        $connection = $this->newActiveConnection(['public-channel']);
        $receiver = $this->newActiveConnection(['public-channel']);

        $message = new Mocks\Message([
            'appId' => '1234',
            'serverId' => $this->channelManager->getServerId(),
            'event' => 'some-event',
            'data' => [
                'channel' => 'public-channel',
                'test' => 'yes',
            ],
            'socketId' => $connection->socketId,
        ]);

        $channel = $this->channelManager->find('1234', 'public-channel');

        $channel->broadcastToEveryoneExcept(
            $message->getPayloadAsObject(), $connection->socketId, '1234', true
        );

        $receiver->assertSentEvent('some-event', $message->getPayloadAsArray());

        $this->getSubscribeClient()
            ->assertNothingDispatched();

        $this->getPublishClient()
            ->assertCalledWithArgs('publish', [
                $this->channelManager->getRedisKey('1234', 'public-channel'),
                $message->getPayload(),
            ]);
    }

    public function test_it_fires_the_event_to_public_channel()
    {
        $this->newActiveConnection(['public-channel']);

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/events';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'POST', $requestPath, [
                'name' => 'some-event',
                'channels' => ['public-channel'],
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

    public function test_it_fires_event_across_servers_when_there_are_not_users_locally_for_public_channel()
    {
        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/events';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'POST', $requestPath, [
                'name' => 'some-event',
                'channels' => ['public-channel'],
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
                    $this->channelManager->getRedisKey('1234', 'public-channel'),
                    json_encode([
                        'event' => 'some-event',
                        'channel' => 'public-channel',
                        'data' => json_encode(['some-data' => 'yes']),
                        'appId' => '1234',
                        'socketId' => null,
                        'serverId' => $this->channelManager->getServerId(),
                    ]),
                ]);
        }
    }

    public function test_it_fires_event_across_servers_when_there_are_users_locally_for_public_channel()
    {
        $wsConnection = $this->newActiveConnection(['public-channel']);

        $connection = new Mocks\Connection;

        $requestPath = '/apps/1234/events';

        $routeParams = [
            'appId' => '1234',
        ];

        $queryString = Pusher::build_auth_query_string(
            'TestKey', 'TestSecret', 'POST', $requestPath, [
                'name' => 'some-event',
                'channels' => ['public-channel'],
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
                    $this->channelManager->getRedisKey('1234', 'public-channel'),
                    json_encode([
                        'event' => 'some-event',
                        'channel' => 'public-channel',
                        'data' => json_encode(['some-data' => 'yes']),
                        'appId' => '1234',
                        'socketId' => null,
                        'serverId' => $this->channelManager->getServerId(),
                    ]),
                ]);
        }

        $wsConnection->assertSentEvent('some-event', [
            'channel' => 'public-channel',
            'data' => json_encode(['some-data' => 'yes']),
        ]);
    }
}
