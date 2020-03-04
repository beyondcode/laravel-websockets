<?php

namespace BeyondCode\LaravelWebSockets\Tests\Statistics\Controllers;

use BeyondCode\LaravelWebSockets\Facades\StatisticsLogger;
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
}
