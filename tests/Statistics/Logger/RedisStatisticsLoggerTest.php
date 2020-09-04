<?php

namespace BeyondCode\LaravelWebSockets\Tests\Statistics\Controllers;

use BeyondCode\LaravelWebSockets\Facades\StatisticsLogger;
use BeyondCode\LaravelWebSockets\Statistics\Logger\RedisStatisticsLogger;
use BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry;
use BeyondCode\LaravelWebSockets\Tests\TestCase;
use Illuminate\Support\Facades\Redis;

class RedisStatisticsLoggerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->runOnlyOnRedisReplication();
    }

    /** @test */
    public function it_counts_connections_on_redis_replication()
    {
        $connections = [];

        $connections[] = $this->getConnectedWebSocketConnection(['channel-1']);
        $connections[] = $this->getConnectedWebSocketConnection(['channel-1']);
        $connections[] = $this->getConnectedWebSocketConnection(['channel-1']);

        $this->assertEquals(3, StatisticsLogger::getForAppId(1234)['peak_connection_count']);

        $this->pusherServer->onClose(array_pop($connections));

        StatisticsLogger::save();

        $this->assertEquals(2, StatisticsLogger::getForAppId(1234)['peak_connection_count']);
    }

    /** @test */
    public function it_counts_unique_connections_no_channel_subscriptions_on_redis()
    {
        Redis::hdel('laravel_database_1234', 'connections');

        $connections = [];

        $connections[] = $this->getConnectedWebSocketConnection(['channel-1', 'channel-2']);
        $connections[] = $this->getConnectedWebSocketConnection(['channel-1', 'channel-2']);
        $connections[] = $this->getConnectedWebSocketConnection(['channel-1']);

        $this->assertEquals(3, StatisticsLogger::getForAppId(1234)['peak_connection_count']);

        $this->pusherServer->onClose(array_pop($connections));
        $this->pusherServer->onClose(array_pop($connections));

        StatisticsLogger::save();

        $this->assertEquals(1, StatisticsLogger::getForAppId(1234)['peak_connection_count']);
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

        $this->assertCount(1, WebSocketsStatisticsEntry::all());

        $entry = WebSocketsStatisticsEntry::first();

        $this->assertEquals(1, $entry->peak_connection_count);
        $this->assertEquals(1, $entry->websocket_message_count);
        $this->assertEquals(1, $entry->api_message_count);
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

        $this->assertCount(1, WebSocketsStatisticsEntry::all());

        $entry = WebSocketsStatisticsEntry::first();

        $this->assertEquals(1, $entry->peak_connection_count);
        $this->assertEquals(1, $entry->websocket_message_count);
        $this->assertEquals(1, $entry->api_message_count);
    }
}
