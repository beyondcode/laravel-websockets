<?php

namespace BeyondCode\LaravelWebSockets\Contracts;

use Amp\Promise;

interface StatisticsCollector
{
    /**
     * Handle the incoming websocket message.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function webSocketMessage($appId): void;

    /**
     * Handle the incoming API message.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function apiMessage($appId): void;

    /**
     * Handle the new connection.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function connection($appId): void;

    /**
     * Handle disconnections.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function disconnection($appId): void;

    /**
     * Save all the stored statistics.
     *
     * @return void
     */
    public function save(): void;

    /**
     * Flush the stored statistics.
     *
     * @return void
     */
    public function flush(): void;

    /**
     * Get the saved statistics.
     *
     * @return \Amp\Promise
     */
    public function getStatistics(): Promise;

    /**
     * Get the saved statistics for an app.
     *
     * @param  string|int  $appId
     * @return \Amp\Promise
     */
    public function getAppStatistics($appId): Promise;

    /**
     * Remove all app traces from the database if no connections have been set
     * in the meanwhile since last save.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function resetAppTraces($appId): void;
}
