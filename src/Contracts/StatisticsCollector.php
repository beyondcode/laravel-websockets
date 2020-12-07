<?php

namespace BeyondCode\LaravelWebSockets\Contracts;

use React\Promise\PromiseInterface;

interface StatisticsCollector
{
    /**
     * Handle the incoming websocket message.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function webSocketMessage($appId);

    /**
     * Handle the incoming API message.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function apiMessage($appId);

    /**
     * Handle the new conection.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function connection($appId);

    /**
     * Handle disconnections.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function disconnection($appId);

    /**
     * Save all the stored statistics.
     *
     * @return void
     */
    public function save();

    /**
     * Flush the stored statistics.
     *
     * @return void
     */
    public function flush();

    /**
     * Get the saved statistics.
     *
     * @return PromiseInterface[array]
     */
    public function getStatistics(): PromiseInterface;

    /**
     * Get the saved statistics for an app.
     *
     * @param  string|int  $appId
     * @return PromiseInterface[\BeyondCode\LaravelWebSockets\Statistics\Statistic|null]
     */
    public function getAppStatistics($appId): PromiseInterface;

    /**
     * Remove all app traces from the database if no connections have been set
     * in the meanwhile since last save.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function resetAppTraces($appId);
}
