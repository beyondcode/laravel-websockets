<?php

namespace BeyondCode\LaravelWebSockets\Facades;

use BeyondCode\LaravelWebSockets\Statistics\Logging\FakeStatisticsLogger;
use Illuminate\Support\Facades\Facade;

/** @see \BeyondCode\LaravelWebSockets\Statistics\Logging\HttpStatisticsLogger */
class StatisticsLogger extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'websockets.statisticslogger';
    }

    public static function fake()
    {
        static::swap(new FakeStatisticsLogger());
    }
}
