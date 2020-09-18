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

    public function test_unsubscribe_from_topic_when_the_last_connection_leaves()
    {
        $connection = $this->newActiveConnection(['public-channel']);

        $this->pusherServer->onClose($connection);

        $this->getSubscribeClient()
            ->assertCalledWithArgs('unsubscribe', [$this->channelManager->getRedisKey('1234')])
            ->assertCalledWithArgs('unsubscribe', [$this->channelManager->getRedisKey('1234', 'public-channel')]);
    }
}
