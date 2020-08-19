<?php

namespace BeyondCode\LaravelWebSockets\Facades;

use BeyondCode\LaravelWebSockets\Statistics\Logger\StatisticsLogger as StatisticsLoggerInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @see   \BeyondCode\LaravelWebSockets\Statistics\Logger\MemoryStatisticsLogger
 * @mixin \BeyondCode\LaravelWebSockets\Statistics\Logger\MemoryStatisticsLogger
 */
class StatisticsLogger extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return StatisticsLoggerInterface::class;
    }
}
