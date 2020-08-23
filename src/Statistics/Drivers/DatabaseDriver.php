<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Drivers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class DatabaseDriver implements StatisticsDriver
{
    /**
     * The model that controls the database table.
     *
     * @var \BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry|null
     */
    protected $record;

    /**
     * Initialize the driver.
     *
     * @param  \BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry|null  $record
     * @return void
     */
    public function __construct($record = null)
    {
        $this->record = $record;
    }

    /**
     * Get the app ID for the stats.
     *
     * @return mixed
     */
    public function getAppId()
    {
        return $this->record->app_id;
    }

    /**
     * Get the time value. Should be Y-m-d H:i:s.
     *
     * @return string
     */
    public function getTime(): string
    {
        return Carbon::parse($this->record->created_at)->toDateTimeString();
    }

    /**
     * Get the peak connection count for the time.
     *
     * @return int
     */
    public function getPeakConnectionCount(): int
    {
        return $this->record->peak_connection_count ?? 0;
    }

    /**
     * Get the websocket messages count for the time.
     *
     * @return int
     */
    public function getWebsocketMessageCount(): int
    {
        return $this->record->websocket_message_count ?? 0;
    }

    /**
     * Get the API message count for the time.
     *
     * @return int
     */
    public function getApiMessageCount(): int
    {
        return $this->record->api_message_count ?? 0;
    }

    /**
     * Create a new statistic in the store.
     *
     * @param  array  $data
     * @return \BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver
     */
    public static function create(array $data): StatisticsDriver
    {
        $class = config('websockets.statistics.database.model');

        return new static($class::create($data));
    }

    /**
     * Get the records to show to the dashboard.
     *
     * @param  mixed  $appId
     * @param  \Illuminate\Http\Request|null  $request
     * @return array
     */
    public static function get($appId, ?Request $request): array
    {
        $class = config('websockets.statistics.database.model');

        $statistics = $class::whereAppId($appId)
            ->latest()
            ->limit(120)
            ->get()
            ->map(function ($statistic) {
                return [
                    'timestamp' => (string) $statistic->created_at,
                    'peak_connection_count' => $statistic->peak_connection_count,
                    'websocket_message_count' => $statistic->websocket_message_count,
                    'api_message_count' => $statistic->api_message_count,
                ];
            })->reverse();

        return [
            'peak_connections' => [
                'x' => $statistics->pluck('timestamp'),
                'y' => $statistics->pluck('peak_connection_count'),
            ],
            'websocket_message_count' => [
                'x' => $statistics->pluck('timestamp'),
                'y' => $statistics->pluck('websocket_message_count'),
            ],
            'api_message_count' => [
                'x' => $statistics->pluck('timestamp'),
                'y' => $statistics->pluck('api_message_count'),
            ],
        ];
    }

    /**
     * Delete statistics from the store,
     * optionally by app id, returning
     * the number of  deleted records.
     *
     * @param  mixed  $appId
     * @return int
     */
    public static function delete($appId = null): int
    {
        $cutOffDate = Carbon::now()->subDay(
            config('websockets.statistics.delete_statistics_older_than_days')
        )->format('Y-m-d H:i:s');

        $class = config('websockets.statistics.database.model');

        return $class::where('created_at', '<', $cutOffDate)
            ->when($appId, function ($query) use ($appId) {
                return $query->whereAppId($appId);
            })
            ->delete();
    }
}
