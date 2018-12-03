<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logging;

use Ratchet\ConnectionInterface;

interface StatisticsLogger
{
    public function logWebSocketMessage(ConnectionInterface $connection);

    public function logApiMessage($appId);

    public function logConnection(ConnectionInterface $connection);

    public function logDisconnection(ConnectionInterface $connection);

    public function save();
}