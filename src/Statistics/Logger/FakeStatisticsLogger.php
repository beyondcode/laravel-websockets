<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logger;

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

    public function save()
    {
    }
}
