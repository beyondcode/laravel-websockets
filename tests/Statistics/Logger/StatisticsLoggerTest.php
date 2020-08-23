<?php

namespace BeyondCode\LaravelWebSockets\Tests\Statistics\Controllers;

use BeyondCode\LaravelWebSockets\Facades\StatisticsLogger;
use BeyondCode\LaravelWebSockets\Statistics\Logger\MemoryStatisticsLogger;
use BeyondCode\LaravelWebSockets\Statistics\Logger\NullStatisticsLogger;
use BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry;
use BeyondCode\LaravelWebSockets\Tests\TestCase;

class StatisticsLoggerTest extends TestCase
{
    /** @test */
    public function it_counts_connections()
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
    public function it_counts_unique_connections_no_channel_subscriptions()
    {
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
}
