<?php

namespace BeyondCode\LaravelWebSockets\Contracts;

use BeyondCode\LaravelWebSockets\Contracts\Promise;

interface StatisticsCollector
{
    /**
     * Handle the incoming websocket message.
     *
     * @param  string  $appId
     * @return void
     */
    public function onReceived(string $appId): void;

    /**
     * Handle the incoming API message.
     *
     * @param  string  $appId
     * @return void
     */
    public function onSent(string $appId): void;

    /**
     * Handle the new connection.
     *
     * @param  string  $appId
     * @return void
     */
    public function onConnected(string $appId): void;

    /**
     * Handle disconnections.
     *
     * @param  string  $appId
     * @return void
     */
    public function onDisconnected(string $appId): void;

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
     * Get the saved statistics, optionally filtered by an App ID.
     *
     * @param  string|null  $appId
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<\Illuminate\Database\Eloquent\Collection<\BeyondCode\LaravelWebSockets\Models\WebSocketsStatisticsEntry>>
     */
    public function getStatistics(string $appId = null): Promise;

    /**
     * Remove all app traces from the database if no connections have been set since last save.
     *
     * @param  string  $appId
     * @return void
     */
    public function resetAppTraces(string $appId): void;
}
