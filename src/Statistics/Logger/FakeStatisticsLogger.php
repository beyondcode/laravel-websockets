<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logger;

use BeyondCode\LaravelWebSockets\Statistics\Http\Controllers\WebsocketStatisticsEntriesController;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use GuzzleHttp\Client;
use Ratchet\ConnectionInterface;

class FakeStatisticsLogger implements StatisticsLogger
{

    public function webSocketMessage(ConnectionInterface $connection)
    {

    }

    public function apiMessage($appId)
    {

    }

    public function connection(ConnectionInterface $connection)
    {

    }

    public function disconnection(ConnectionInterface $connection)
    {

    }

    protected function initializeStatistics($id)
    {

    }

    public function save()
    {

    }
}