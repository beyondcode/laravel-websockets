<?php

namespace BeyondCode\LaravelWebSockets\Test;

use Carbon\Carbon;

class LocalPongRemovalTest extends TestCase
{
    public function test_not_ponged_connections_do_get_removed_on_local_for_public_channels()
    {
        $this->runOnlyOnLocalReplication();

        $activeConnection = $this->newActiveConnection(['public-channel']);
        $obsoleteConnection = $this->newActiveConnection(['public-channel']);

        // The active connection just pinged, it should not be closed.
        $activeConnection->lastPongedAt = Carbon::now();
        $obsoleteConnection->lastPongedAt = Carbon::now()->subDays(1);

        $this->channelManager->updateConnectionInChannels($activeConnection);
        $this->channelManager->updateConnectionInChannels($obsoleteConnection);

        $this->channelManager->getGlobalConnectionsCount('1234', 'public-channel')->then(function ($count) {
            $this->assertEquals(2, $count);
        });

        $this->channelManager->removeObsoleteConnections();

        $this->channelManager->getGlobalConnectionsCount('1234', 'public-channel')->then(function ($count) {
            $this->assertEquals(1, $count);
        });

        $this->channelManager->getLocalConnections()->then(function ($connections) use ($activeConnection) {
            $connection = $connections[$activeConnection->socketId];

            $this->assertEquals($activeConnection->socketId, $connection->socketId);
        });
    }

    public function test_not_ponged_connections_do_get_removed_on_local_for_private_channels()
    {
        $this->runOnlyOnLocalReplication();

        $activeConnection = $this->newPrivateConnection('private-channel');
        $obsoleteConnection = $this->newPrivateConnection('private-channel');

        // The active connection just pinged, it should not be closed.
        $activeConnection->lastPongedAt = Carbon::now();
        $obsoleteConnection->lastPongedAt = Carbon::now()->subDays(1);

        $this->channelManager->updateConnectionInChannels($activeConnection);
        $this->channelManager->updateConnectionInChannels($obsoleteConnection);

        $this->channelManager->getGlobalConnectionsCount('1234', 'private-channel')->then(function ($count) {
            $this->assertEquals(2, $count);
        });

        $this->channelManager->removeObsoleteConnections();

        $this->channelManager->getGlobalConnectionsCount('1234', 'private-channel')->then(function ($count) {
            $this->assertEquals(1, $count);
        });

        $this->channelManager->getLocalConnections()->then(function ($connections) use ($activeConnection) {
            $connection = $connections[$activeConnection->socketId];

            $this->assertEquals($activeConnection->socketId, $connection->socketId);
        });
    }

    public function test_not_ponged_connections_do_get_removed_on_local_for_presence_channels()
    {
        $this->runOnlyOnLocalReplication();

        $activeConnection = $this->newPresenceConnection('presence-channel', ['user_id' => 1]);
        $obsoleteConnection = $this->newPresenceConnection('presence-channel', ['user_id' => 2]);

        // The active connection just pinged, it should not be closed.
        $activeConnection->lastPongedAt = Carbon::now();
        $obsoleteConnection->lastPongedAt = Carbon::now()->subDays(1);

        $this->channelManager->updateConnectionInChannels($activeConnection);
        $this->channelManager->updateConnectionInChannels($obsoleteConnection);

        $this->channelManager->getGlobalConnectionsCount('1234', 'presence-channel')->then(function ($count) {
            $this->assertEquals(2, $count);
        });

        $this->channelManager->getChannelMembers('1234', 'presence-channel')->then(function ($members) {
            $this->assertCount(2, $members);
        });

        $this->channelManager->removeObsoleteConnections();

        $this->channelManager->getGlobalConnectionsCount('1234', 'presence-channel')->then(function ($count) {
            $this->assertEquals(1, $count);
        });

        $this->channelManager->getLocalConnections()->then(function ($connections) use ($activeConnection) {
            $connection = $connections[$activeConnection->socketId];

            $this->assertEquals($activeConnection->socketId, $connection->socketId);
        });

        $this->channelManager->getChannelMembers('1234', 'presence-channel')->then(function ($members) {
            $this->assertCount(1, $members);
        });
    }
}
