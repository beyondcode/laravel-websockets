<?php

namespace BeyondCode\LaravelWebSockets\Contracts;

use Carbon\Carbon;

interface StatisticsStore
{
    /**
     * Store a new record in the database and return
     * the created instance.
     *
     * @param  array  $data
     * @return mixed
     */
    public static function store(array $data);

    /**
     * Delete records older than the given moment,
     * for a specific app id (if given), returning
     * the amount of deleted records.
     *
     * @param  \Carbon\Carbon  $moment
     * @param  string|int|null  $appId
     * @return int
     */
    public static function delete(Carbon $moment, $appId = null): int;

    /**
     * Get the query result as eloquent collection.
     *
     * @param  callable  $processQuery
     * @return \Illuminate\Support\Collection
     */
    public function getRawRecords(callable $processQuery = null);

    /**
     * Get the results for a specific query.
     *
     * @param  callable  $processQuery
     * @param  callable  $processCollection
     * @return array
     */
    public function getRecords(callable $processQuery = null, callable $processCollection = null): array;

    /**
     * Get the results for a specific query into a
     * format that is easily to read for graphs.
     *
     * @param  callable  $processQuery
     * @param  callable  $processCollection
     * @return array
     */
    public function getForGraph(callable $processQuery = null, callable $processCollection = null): array;
}
