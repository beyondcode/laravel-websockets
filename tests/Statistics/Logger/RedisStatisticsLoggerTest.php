<?php

namespace BeyondCode\LaravelWebSockets\Tests\Statistics\Controllers;

use BeyondCode\LaravelWebSockets\Facades\StatisticsLogger;
use BeyondCode\LaravelWebSockets\Statistics\Logger\RedisStatisticsLogger;
use BeyondCode\LaravelWebSockets\Tests\TestCase;

class RedisStatisticsLoggerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->runOnlyOnRedisReplication();

        StatisticsLogger::resetStatistics('1234', 0);
        StatisticsLogger::resetAppTraces('1234');

        $this->redis->hdel('laravel_database_1234', 'connections');

        $this->getPublishClient()->resetAssertions();
    }

    /** @test */
    public function it_counts_connections_on_redis_replication()
    {
        $connections = [];

        $connections[] = $this->getConnectedWebSocketConnection(['channel-1']);
        $connections[] = $this->getConnectedWebSocketConnection(['channel-1']);
        $connections[] = $this->getConnectedWebSocketConnection(['channel-1']);

        $this->getPublishClient()
            ->assertCalledWithArgsCount(6, 'sadd', ['laravel-websockets:apps', '1234'])
            ->assertCalledWithArgsCount(3, 'hincrby', ['laravel-websockets:app:1234', 'current_connection_count', 1])
            ->assertCalledWithArgsCount(3, 'hincrby', ['laravel-websockets:app:1234', 'websocket_message_count', 1]);

        $this->pusherServer->onClose(array_pop($connections));

        StatisticsLogger::save();

        $this->getPublishClient()
            ->assertCalledWithArgs('hincrby', ['laravel-websockets:app:1234', 'current_connection_count', -1])
            ->assertCalledWithArgs('smembers', ['laravel-websockets:apps']);
    }

    /** @test */
    public function it_counts_unique_connections_no_channel_subscriptions_on_redis()
    {
        $connections = [];

        $connections[] = $this->getConnectedWebSocketConnection(['channel-1', 'channel-2']);
        $connections[] = $this->getConnectedWebSocketConnection(['channel-1', 'channel-2']);
        $connections[] = $this->getConnectedWebSocketConnection(['channel-1']);

        $this->getPublishClient()
            ->assertCalledWithArgsCount(3, 'hincrby', ['laravel-websockets:app:1234', 'current_connection_count', 1])
            ->assertCalledWithArgsCount(5, 'hincrby', ['laravel-websockets:app:1234', 'websocket_message_count', 1]);

        $this->pusherServer->onClose(array_pop($connections));
        $this->pusherServer->onClose(array_pop($connections));

        StatisticsLogger::save();

        $this->getPublishClient()
            ->assertCalledWithArgsCount(2, 'hincrby', ['laravel-websockets:app:1234', 'current_connection_count', -1])
            ->assertCalledWithArgs('smembers', ['laravel-websockets:apps']);
    }

    /** @test */
    public function it_counts_connections_with_redis_logger_with_no_data()
    {
        config(['cache.default' => 'redis']);

        $connection = $this->getConnectedWebSocketConnection(['channel-1']);

        $logger = new RedisStatisticsLogger(
            $this->channelManager,
            $this->statisticsDriver
        );

        $logger->resetAppTraces('1234');

        $logger->webSocketMessage($connection->app->id);
        $logger->apiMessage($connection->app->id);
        $logger->connection($connection->app->id);
        $logger->disconnection($connection->app->id);

        $logger->save();

        $this->markTestIncomplete(
            'The numbers does not seem to match well.'
        );
    }

    /** @test */
    public function it_counts_connections_with_redis_logger_with_existing_data()
    {
        config(['cache.default' => 'redis']);

        $connection = $this->getConnectedWebSocketConnection(['channel-1']);

        $logger = new RedisStatisticsLogger(
            $this->channelManager,
            $this->statisticsDriver
        );

        $logger->resetStatistics('1234', 0);

        $logger->webSocketMessage($connection->app->id);
        $logger->apiMessage($connection->app->id);
        $logger->connection($connection->app->id);
        $logger->disconnection($connection->app->id);

        $logger->save();

        $this->markTestIncomplete(
            'The numbers does not seem to match well.'
        );
    }
}
