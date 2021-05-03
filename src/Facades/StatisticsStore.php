<?php

namespace BeyondCode\LaravelWebSockets\Facades;

use BeyondCode\LaravelWebSockets\Contracts\StatisticsStore as StatisticsStoreInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void store(array $data);
 * @method static int delete(\Illuminate\Support\Carbon $moment, $appId = null);
 * @method static \Illuminate\Support\Collection getRawRecords(callable $processQuery = null);
 * @method static array getRecords(callable $processQuery = null, callable $processCollection = null);
 * @method static array getForGraph(callable $processQuery = null, callable $processCollection = null);
 */
class StatisticsStore extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return StatisticsStoreInterface::class;
    }
}
