<?php

namespace BeyondCode\LaravelWebSockets\Tests\Statistics\Controllers;

use BeyondCode\LaravelWebSockets\Facades\StatisticsLogger;
use BeyondCode\LaravelWebSockets\Statistics\Logger\MemoryStatisticsLogger;
use BeyondCode\LaravelWebSockets\Statistics\Logger\NullStatisticsLogger;
use BeyondCode\LaravelWebSockets\Statistics\Logger\RedisStatisticsLogger;
use BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry;
use BeyondCode\LaravelWebSockets\Tests\TestCase;
use Illuminate\Support\Facades\Redis;

class StatisticsLoggerTest extends TestCase
{
    /** @test */
    public function it_counts_connections()
    {
        $this->runOnlyOnLocalReplication();

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
    public function it_counts_connections_on_redis_replication()
    {
        $this->runOnlyOnRedisReplication();

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
    public function it_counts_unique_connections_no_channel_subscriptions()
    {
        $this->runOnlyOnLocalReplication();

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
    public function it_counts_unique_connections_no_channel_subscriptions_on_redis()
    {
        $this->runOnlyOnRedisReplication();

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
    public function it_counts_connections_with_memory_logger()
    {
        $connection = $this->getConnectedWebSocketConnection(['channel-1']);

        $logger = new MemoryStatisticsLogger(
            $this->channelManager,
            $this->statisticsDriver
        );

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
    public function it_counts_connections_with_null_logger()
    {
        $connection = $this->getConnectedWebSocketConnection(['channel-1']);

        $logger = new NullStatisticsLogger(
            $this->channelManager,
            $this->statisticsDriver
        );

        $logger->webSocketMessage($connection->app->id);
        $logger->apiMessage($connection->app->id);
        $logger->connection($connection->app->id);
        $logger->disconnection($connection->app->id);

        $logger->save();

        $this->assertCount(0, WebSocketsStatisticsEntry::all());
    }

    /** @test */
    public function it_counts_connections_with_redis_logger_with_no_data()
    {
        $this->runOnlyOnRedisReplication();

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
        $this->runOnlyOnRedisReplication();

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
