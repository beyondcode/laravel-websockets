<?php

namespace BeyondCode\LaravelWebSockets\Contracts;

use Carbon\Carbon;
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
     * @param  \Carbon\Carbon  $moment
     * @param  string|int|null  $appId
     *
     * @return int  Returning the amount of deleted records.
     */
    public function delete(Carbon $moment, $appId = null): int;

    /**
     * Get the query result as eloquent collection.
     *
     * @param  callable|null  $processQuery
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRawRecords(callable $processQuery = null): Collection;

    /**
     * Get the results for a specific query.
     *
     * @param  callable|null  $processQuery
     * @param  callable|null  $processCollection
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecords(callable $processQuery = null, callable $processCollection = null): Collection;

    /**
     * Get the results for a specific query into a format that is easily to read for graphs.
     *
     * @param  callable|null  $processQuery
     * @param  callable|null  $processCollection
     *
     * @return array
     */
    public function getForGraph(callable $processQuery = null, callable $processCollection = null): array;
}
