<?php

namespace BeyondCode\LaravelWebSockets\Test;

use Carbon\Carbon;

class RedisPongRemovalTest extends TestCase
{
    public function test_not_ponged_connections_do_get_removed_on_redis_for_public_channels()
    {
        $this->runOnlyOnRedisReplication();

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

    public function test_not_ponged_connections_do_get_removed_on_redis_for_private_channels()
    {
        $this->runOnlyOnRedisReplication();

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

    public function test_not_ponged_connections_do_get_removed_on_redis_for_presence_channels()
    {
        $this->runOnlyOnRedisReplication();

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
}
