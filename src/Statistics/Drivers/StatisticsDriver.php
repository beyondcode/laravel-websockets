<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Drivers;

interface StatisticsDriver
{
    /**
     * Initialize the driver with a stored record.
     *
     * @param  mixed  $record
     * @return void
     */
    public function __construct($record = null);

    /**
     * Get the app ID for the stats.
     *
     * @return mixed
     */
    public function getAppId();

    /**
     * Get the time value. Should be Y-m-d H:i:s.
     *
     * @return string
     */
    public function getTime(): string;

    /**
     * Get the peak connection count for the time.
     *
     * @return int
     */
    public function getPeakConnectionCount(): int;

    /**
     * Get the websocket messages count for the time.
     *
     * @return int
     */
    public function getWebsocketMessageCount(): int;

    /**
     * Get the API message count for the time.
     *
     * @return int
     */
    public function getApiMessageCount(): int;

    /**
     * Create a new statistic in the store.
     *
     * @param  array  $data
     * @return \BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver
     */
    public static function create(array $data): StatisticsDriver;

    /**
     * Delete statistics from the store,
     * optionally by app id, returning
     * the number of  deleted records.
     *
     * @param  mixed  $appId
     * @return int
     */
    public static function delete($appId = null): int;
}
