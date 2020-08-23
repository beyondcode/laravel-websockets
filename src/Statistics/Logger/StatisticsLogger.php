<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logger;

interface StatisticsLogger
{
    /**
     * Handle the incoming websocket message.
     *
     * @param  mixed  $appId
     * @return void
     */
    public function webSocketMessage($appId);

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
     * @param  mixed  $appId
     * @return void
     */
    public function connection($appId);

    /**
     * Handle disconnections.
     *
     * @param  mixed  $appId
     * @return void
     */
    public function disconnection($appId);

    /**
     * Save all the stored statistics.
     *
     * @return void
     */
    public function save();
}
