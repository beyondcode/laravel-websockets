<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logger;

use Ratchet\connectionInterface;

interface StatisticsLogger
{
    public function webSocketMessage(connectionInterface $connection);

    public function apiMessage($appId);

    public function connection(connectionInterface $connection);

    public function disconnection(connectionInterface $connection);

    public function save();
}
