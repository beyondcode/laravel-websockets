<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logger;

use BeyondCode\LaravelWebSockets\Statistics\Http\Controllers\WebsocketStatisticsEntriesController;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use GuzzleHttp\Client;
use Ratchet\ConnectionInterface;

class FakeStatisticsLogger implements StatisticsLogger
{

    public function logWebSocketMessage(ConnectionInterface $connection)
    {

    }

    public function logApiMessage($appId)
    {

    }

    public function logConnection(ConnectionInterface $connection)
    {

    }

    public function logDisconnection(ConnectionInterface $connection)
    {

    }

    protected function initializeStatistics($id)
    {

    }

    public function save()
    {

    }
}