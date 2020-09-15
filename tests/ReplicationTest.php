<?php

namespace BeyondCode\LaravelWebSockets\Test;

class ReplicationTest extends TestCase
{
    public function test_events_get_replicated_across_connections()
    {
        $this->runOnlyOnRedisReplication();

        $connection = $this->newActiveConnection(['public-channel']);

        $message = [
            'appId' => '1234',
            'serverId' => 0,
            'event' => 'some-event',
            'data' => [
                'channel' => 'public-channel',
                'test' => 'yes',
            ],
        ];

        $channel = $this->channelManager->find('1234', 'public-channel');

        $channel->broadcastToEveryoneExcept(
            (object) $message, null, '1234', true
        );

        $connection->assertSentEvent('some-event', [
            'appId' => '1234',
            'serverId' => $this->channelManager->getServerId(),
            'data' => ['channel' => 'public-channel', 'test' => 'yes'],
        ]);
    }

    public function test_not_ponged_connections_do_get_removed_for_public_channels()
    {
        $this->runOnlyOnRedisReplication();

        $connection = $this->newActiveConnection(['public-channel']);

        // Make the connection look like it was lost 1 day ago.
        $this->channelManager->addConnectionToSet($connection, now()->subDays(1));

        $this->channelManager
            ->getConnectionsFromSet(0, now()->subMinutes(2)->format('U'))
            ->then(function ($expiredConnections) {
                $this->assertCount(1, $expiredConnections);
            });

        $this->channelManager->removeObsoleteConnections();

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'public-channel')
            ->then(function ($count) {
                $this->assertEquals(0, $count);
            });

        $this->channelManager
            ->getConnectionsFromSet(0, now()->subMinutes(2)->format('U'))
            ->then(function ($expiredConnections) {
                $this->assertCount(0, $expiredConnections);
            });
    }

    public function test_not_ponged_connections_do_get_removed_for_private_channels()
    {
        $this->runOnlyOnRedisReplication();

        $connection = $this->newPrivateConnection('private-channel');

        // Make the connection look like it was lost 1 day ago.
        $this->channelManager->addConnectionToSet($connection, now()->subDays(1));

        $this->channelManager
            ->getConnectionsFromSet(0, now()->subMinutes(2)->format('U'))
            ->then(function ($expiredConnections) {
                $this->assertCount(1, $expiredConnections);
            });

        $this->channelManager->removeObsoleteConnections();

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'private-channel')
            ->then(function ($count) {
                $this->assertEquals(0, $count);
            });

        $this->channelManager
            ->getConnectionsFromSet(0, now()->subMinutes(2)->format('U'))
            ->then(function ($expiredConnections) {
                $this->assertCount(0, $expiredConnections);
            });
    }

    public function test_not_ponged_connections_do_get_removed_for_presence_channels()
    {
        $this->runOnlyOnRedisReplication();

        $connection = $this->newPresenceConnection('presence-channel');

        // Make the connection look like it was lost 1 day ago.
        $this->channelManager->addConnectionToSet($connection, now()->subDays(1));

        $this->channelManager
            ->getConnectionsFromSet(0, now()->subMinutes(2)->format('U'))
            ->then(function ($expiredConnections) {
                $this->assertCount(1, $expiredConnections);
            });

        $this->channelManager
            ->getChannelMembers('1234', 'presence-channel')
            ->then(function ($members) {
                $this->assertCount(1, $members);
            });

        $this->channelManager->removeObsoleteConnections();

        $this->channelManager
            ->getGlobalConnectionsCount('1234', 'private-channel')
            ->then(function ($count) {
                $this->assertEquals(0, $count);
            });

        $this->channelManager
            ->getConnectionsFromSet(0, now()->subMinutes(2)->format('U'))
            ->then(function ($expiredConnections) {
                $this->assertCount(0, $expiredConnections);
            });

        $this->channelManager
            ->getChannelMembers('1234', 'presence-channel')
            ->then(function ($members) {
                $this->assertCount(0, $members);
            });
    }
}
