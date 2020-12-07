<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Stores;

use BeyondCode\LaravelWebSockets\Contracts\StatisticsStore;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DatabaseStore implements StatisticsStore
{
    /**
     * The model that will interact with the database.
     *
     * @var string
     */
    public static $model = \BeyondCode\LaravelWebSockets\Models\WebSocketsStatisticsEntry::class;

    /**
     * Store a new record in the database and return
     * the created instance.
     *
     * @param  array  $data
     * @return mixed
     */
    public static function store(array $data)
    {
        return static::$model::create($data);
    }

    /**
     * Delete records older than the given moment,
     * for a specific app id (if given), returning
     * the amount of deleted records.
     *
     * @param  \Carbon\Carbon  $moment
     * @param  string|int|null  $appId
     * @return int
     */
    public static function delete(Carbon $moment, $appId = null): int
    {
        return static::$model::where('created_at', '<', $moment->toDateTimeString())
            ->when(! is_null($appId), function ($query) use ($appId) {
                return $query->whereAppId($appId);
            })
            ->delete();
    }

    /**
     * Get the query result as eloquent collection.
     *
     * @param  callable  $processQuery
     * @return \Illuminate\Support\Collection
     */
    public function getRawRecords(callable $processQuery = null)
    {
        return static::$model::query()
            ->when(! is_null($processQuery), function ($query) use ($processQuery) {
                return call_user_func($processQuery, $query);
            }, function ($query) {
                return $query->latest()->limit(120);
            })->get();
    }

    /**
     * Get the results for a specific query.
     *
     * @param  callable  $processQuery
     * @param  callable  $processCollection
     * @return array
     */
    public function getRecords(callable $processQuery = null, callable $processCollection = null): array
    {
        return $this->getRawRecords($processQuery)
            ->when(! is_null($processCollection), function ($collection) use ($processCollection) {
                return call_user_func($processCollection, $collection);
            })
            ->map(function (Model $statistic) {
                return $this->statisticToArray($statistic);
            })
            ->toArray();
    }

    /**
     * Get the results for a specific query into a
     * format that is easily to read for graphs.
     *
     * @param  callable  $processQuery
     * @param  callable  $processCollection
     * @return array
     */
    public function getForGraph(callable $processQuery = null, callable $processCollection = null): array
    {
        $statistics = collect(
            $this->getRecords($processQuery, $processCollection)
        );

        return $this->statisticsToGraph($statistics);
    }

    /**
     * Turn the statistic model to an array.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $statistic
     * @return array
     */
    protected function statisticToArray(Model $statistic): array
    {
        return [
            'timestamp' => (string) $statistic->created_at,
            'peak_connections_count' => $statistic->peak_connections_count,
            'websocket_messages_count' => $statistic->websocket_messages_count,
            'api_messages_count' => $statistic->api_messages_count,
        ];
    }

    /**
     * Turn the statistics collection to an array used for graph.
     *
     * @param  \Illuminate\Support\Collection  $statistics
     * @return array
     */
    protected function statisticsToGraph(Collection $statistics): array
    {
        return [
            'peak_connections' => [
                'x' => $statistics->pluck('timestamp')->toArray(),
                'y' => $statistics->pluck('peak_connections_count')->toArray(),
            ],
            'websocket_messages_count' => [
                'x' => $statistics->pluck('timestamp')->toArray(),
                'y' => $statistics->pluck('websocket_messages_count')->toArray(),
            ],
            'api_messages_count' => [
                'x' => $statistics->pluck('timestamp')->toArray(),
                'y' => $statistics->pluck('api_messages_count')->toArray(),
            ],
        ];
    }
}
