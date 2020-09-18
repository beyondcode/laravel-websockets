<?php

namespace BeyondCode\LaravelWebSockets\Test;

use Carbon\Carbon;

class ReplicationTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->runOnlyOnRedisReplication();
    }

    public function test_publishing_client_gets_subscribed()
    {
        $this->newActiveConnection(['public-channel']);

        $this->getSubscribeClient()
            ->assertCalledWithArgs('subscribe', [$this->channelManager->getRedisKey('1234')])
            ->assertCalledWithArgs('subscribe', [$this->channelManager->getRedisKey('1234', 'public-channel')]);
    }

    public function test_events_get_replicated_across_connections_for_public_channels()
    {
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
                $message->getPayload()
            ]);
    }

    public function test_events_get_replicated_across_connections_for_private_channels()
    {
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

        $this->getPublishClient()
            ->assertCalledWithArgs('publish', [
                $this->channelManager->getRedisKey('1234', 'private-channel'),
                $message->getPayload()
            ]);
    }

    public function test_events_get_replicated_across_connections_for_presence_channels()
    {
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
                $message->getPayload()
            ]);
    }

    public function test_not_ponged_connections_do_get_removed_for_public_channels()
    {
        $activeConnection = $this->newActiveConnection(['public-channel']);
        $obsoleteConnection = $this->newActiveConnection(['public-channel']);

        // The active connection just pinged, it should not be closed.
        $this->channelManager->addConnectionToSet($activeConnection, Carbon::now());

        // Make the connection look like it was lost 1 day ago.
        $this->channelManager->addConnectionToSet($obsoleteConnection, Carbon::now()->subDays(1));

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'public-channel')
            ->then(function ($count) {
                $this->assertEquals(2, $count);
            });

        $this->channelManager
            ->getConnectionsFromSet(0, Carbon::now()->subMinutes(2)->format('U'))
            ->then(function ($expiredConnections) {
                $this->assertCount(1, $expiredConnections);
            });

        $this->channelManager->removeObsoleteConnections();

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'public-channel')
            ->then(function ($count) {
                $this->assertEquals(1, $count);
            });

        $this->channelManager
            ->getConnectionsFromSet(0, Carbon::now()->subMinutes(2)->format('U'))
            ->then(function ($expiredConnections) {
                $this->assertCount(0, $expiredConnections);
            });
    }

    public function test_not_ponged_connections_do_get_removed_for_private_channels()
    {
        $activeConnection = $this->newPrivateConnection('private-channel');
        $obsoleteConnection = $this->newPrivateConnection('private-channel');

        // The active connection just pinged, it should not be closed.
        $this->channelManager->addConnectionToSet($activeConnection, Carbon::now());

        // Make the connection look like it was lost 1 day ago.
        $this->channelManager->addConnectionToSet($obsoleteConnection, Carbon::now()->subDays(1));

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'private-channel')
            ->then(function ($count) {
                $this->assertEquals(2, $count);
            });

        $this->channelManager
            ->getConnectionsFromSet(0, Carbon::now()->subMinutes(2)->format('U'))
            ->then(function ($expiredConnections) {
                $this->assertCount(1, $expiredConnections);
            });

        $this->channelManager->removeObsoleteConnections();

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'private-channel')
            ->then(function ($count) {
                $this->assertEquals(1, $count);
            });

        $this->channelManager
            ->getConnectionsFromSet(0, Carbon::now()->subMinutes(2)->format('U'))
            ->then(function ($expiredConnections) {
                $this->assertCount(0, $expiredConnections);
            });
    }

    public function test_not_ponged_connections_do_get_removed_for_presence_channels()
    {
        $activeConnection = $this->newPresenceConnection('presence-channel', ['user_id' => 1]);
        $obsoleteConnection = $this->newPresenceConnection('presence-channel', ['user_id' => 2]);

        // The active connection just pinged, it should not be closed.
        $this->channelManager->addConnectionToSet($activeConnection, Carbon::now());

        // Make the connection look like it was lost 1 day ago.
        $this->channelManager->addConnectionToSet($obsoleteConnection, Carbon::now()->subDays(1));

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'presence-channel')
            ->then(function ($count) {
                $this->assertEquals(2, $count);
            });

        $this->channelManager
            ->getConnectionsFromSet(0, Carbon::now()->subMinutes(2)->format('U'))
            ->then(function ($expiredConnections) {
                $this->assertCount(1, $expiredConnections);
            });

        $this->channelManager
            ->getChannelMembers('1234', 'presence-channel')
            ->then(function ($members) {
                $this->assertCount(2, $members);
            });

        $this->channelManager->removeObsoleteConnections();

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'presence-channel')
            ->then(function ($count) {
                $this->assertEquals(1, $count);
            });

        $this->channelManager
            ->getConnectionsFromSet(0, Carbon::now()->subMinutes(2)->format('U'))
            ->then(function ($expiredConnections) {
                $this->assertCount(0, $expiredConnections);
            });

        $this->channelManager
            ->getChannelMembers('1234', 'presence-channel')
            ->then(function ($members) {
                $this->assertCount(1, $members);
            });
    }

    public function test_events_are_processed_by_on_message_on_public_channels()
    {
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

    public function test_events_are_processed_by_on_message_on_private_channels()
    {
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

    public function test_events_are_processed_by_on_message_on_presence_channels()
    {
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
}
