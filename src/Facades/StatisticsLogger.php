<?php

namespace BeyondCode\LaravelWebSockets\Facades;

use Illuminate\Support\Facades\Facade;

/** @see \BeyondCode\LaravelWebSockets\Statistics\Logging\Logger */
class StatisticsLogger extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'websockets.statisticslogger';
    }
}
