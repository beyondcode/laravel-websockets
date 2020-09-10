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
}
