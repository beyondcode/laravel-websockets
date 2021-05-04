<?php

namespace BeyondCode\LaravelWebSockets\Contracts;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;

interface StatisticsStore
{
    /**
     * Store a new record in the database and return the created instance.
     *
     * @param  array  $data
     *
     * @return mixed
     */
    public function store(array $data);

    /**
     * Delete records older than the given moment for all app ids, or an specific app id.
     *
     * @param  \DateTimeInterface  $datetime
     * @param  string|null  $appId
     *
     * @return int  Amount of deleted records.
     */
    public function delete(DateTimeInterface $datetime, string $appId = null): int;

    /**
     * Get the query result as Eloquent Collection.
     *
     * @param  callable<\Illuminate\Database\Eloquent\Builder>|null  $filter
     *
     * @return \Illuminate\Database\Eloquent\Collection<\BeyondCode\LaravelWebSockets\Models\WebSocketsStatisticsEntry>
     */
    public function getRawRecords(callable $filter = null): Collection;

    /**
     * Get the results for a specific query.
     *
     * @param  callable<\Illuminate\Database\Eloquent\Builder>|null  $queryFilter
     * @param  callable<\Illuminate\Database\Eloquent\Collection<\BeyondCode\LaravelWebSockets\Models\WebSocketsStatisticsEntry>>|null  $collectionFilter
     *
     * @return \Illuminate\Database\Eloquent\Collection<\BeyondCode\LaravelWebSockets\Models\WebSocketsStatisticsEntry>
     */
    public function getRecords(callable $queryFilter = null, callable $collectionFilter = null): Collection;

    /**
     * Get the results for a specific query into a format that is easily to read for graphs.
     *
     * @param  callable<\Illuminate\Database\Eloquent\Builder>|null  $queryFilter
     * @param  callable<\Illuminate\Database\Eloquent\Collection<\BeyondCode\LaravelWebSockets\Models\WebSocketsStatisticsEntry>>|null  $collectionFilter
     *
     * @return array
     */
    public function getForGraph(callable $queryFilter = null, callable $collectionFilter = null): array;
}
