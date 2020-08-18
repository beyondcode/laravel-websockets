<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logger;

use Ratchet\connectionInterface;

interface StatisticsLogger
{
    /**
     * Handle the incoming websocket message.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function webSocketMessage(connectionInterface $connection);

    /**
     * Handle the incoming API message.
     *
     * @param  mixed  $appId
     * @return void
     */
    public function apiMessage($appId);

    /**
     * Handle the new conection.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function connection(connectionInterface $connection);

    /**
     * Handle disconnections.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function disconnection(connectionInterface $connection);

    /**
     * Save all the stored statistics.
     *
     * @return void
     */
    public function save();
}
