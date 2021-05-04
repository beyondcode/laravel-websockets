<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Stores;

use BeyondCode\LaravelWebSockets\Contracts\StatisticsStore;
use BeyondCode\LaravelWebSockets\Models\WebSocketsStatisticsEntry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class DatabaseStore implements StatisticsStore
{
    /**
     * The model that will interact with the database.
     *
     * @var string
     */
    public $model = WebSocketsStatisticsEntry::class;

    /**
     * Creates a new Model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function newModel(): Model
    {
        return (new $this->model);
    }

    /**
     * Creates a new query for the current model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function newQuery(): Builder
    {
        return $this->newModel()->newQuery();
    }

    /**
     * Store a new record in the database and return the created instance.
     *
     * @param  array  $data
     * @return mixed
     */
    public function store(array $data)
    {
        return tap($this->newModel()->fill($data))->save();
    }

    /**
     * Delete records older than the given moment, for a specific app id (if given).
     *
     * @param  \Carbon\Carbon  $datetime
     * @param  string|int|null  $appId
     *
     * @return int  The amount of deleted records
     */
    public function delete(Carbon $datetime, $appId = null): int
    {
        return $this->newQuery()
            ->where($this->newModel()->getCreatedAtColumn(), '<', $datetime->toDateTimeString())
            ->when($appId, static function (Builder $query) use ($appId): void {
                 $query->where('app_id', $appId);
            })
            ->delete();
    }

    /**
     * Get the query result as eloquent collection.
     *
     * @param  callable|null  $filter
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRawRecords(callable $filter = null): Collection
    {
        return $this->newQuery()
            ->when($filter, static function (Builder $query) use ($filter): void {
                $filter($query);
            }, static function (Builder $query): void {
                $query->latest()->limit(120);
            })->get();
    }

    /**
     * Get the results for a specific query.
     *
     * @param  callable|null  $queryFilter
     * @param  callable|null  $collectionFilter
     *
     * @return array
     */
    public function getRecords(callable $queryFilter = null, callable $collectionFilter = null): Collection
    {
        return $this->getRawRecords($queryFilter)
            ->when($collectionFilter, static function ($collection) use ($collectionFilter): Collection {
                return $collectionFilter($collection);
            })
            ->map(function (Model $statistic): array {
                return $this->statisticToArray($statistic);
            });
    }

    /**
     * Get the results for a specific query into a
     * format that is easily to read for graphs.
     *
     * @param  callable|null  $processQuery
     * @param  callable|null  $processCollection
     *
     * @return array
     */
    public function getForGraph(callable $processQuery = null, callable $processCollection = null): array
    {
        return $this->statisticsToGraph($this->getRecords($processQuery, $processCollection));
    }

    /**
     * Turn the statistic model to an array.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\BeyondCode\LaravelWebSockets\Models\WebSocketsStatisticsEntry  $statistic
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
     * @param  \Illuminate\Database\Eloquent\Collection  $statistics
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
