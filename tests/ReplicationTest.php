<?php

namespace BeyondCode\LaravelWebSockets\Test;

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

    public function test_events_get_replicated_across_connections()
    {
        $connection = $this->newActiveConnection(['public-channel']);

        $message = [
            'appId' => '1234',
            'serverId' => $this->channelManager->getServerId(),
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

        $this->getSubscribeClient()
            ->assertNothingDispatched();

        $this->getPublishClient()
            ->assertCalledWithArgs('publish', [
                $this->channelManager->getRedisKey('1234', 'public-channel'),
                json_encode($message),
            ]);
    }

    public function test_not_ponged_connections_do_get_removed_for_public_channels()
    {
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
