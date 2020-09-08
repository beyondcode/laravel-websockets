<?php

namespace BeyondCode\LaravelWebSockets\Tests\Statistics\Controllers;

use BeyondCode\LaravelWebSockets\Facades\StatisticsLogger;
use BeyondCode\LaravelWebSockets\Statistics\Logger\RedisStatisticsLogger;
use BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry;
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
    }ublic function it_counts_connections_with_redis_logger_with_no_data()
    {
        config(['cache.default' => 'redis']);

        $logger = new RedisStatisticsLogger(
            $this->channelManager,
            $this->statisticsDriver
        );

        $logger->resetAppTraces('1');
        $logger->resetAppTraces('1234');

        $connection = $this->getConnectedWebSocketConnection(['channel-1']);

        $logger->apiMessage($connection->app->id);

        $logger->save();

        $this->assertCount(1, WebSocketsStatisticsEntry::all());

        $entry = WebSocketsStatisticsEntry::first();

        $this->assertEquals(1, $entry->peak_connection_count);
        $this->assertEquals(1, $entry->websocket_message_count);
        $this->assertEquals(1, $entry->api_message_count);
    }
}
