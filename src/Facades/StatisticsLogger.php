<?php

namespace BeyondCode\LaravelWebSockets\Facades;

use BeyondCode\LaravelWebSockets\Statistics\Logger\FakeStatisticsLogger;
use Illuminate\Support\Facades\Facade;

/** @see \BeyondCode\LaravelWebSockets\Statistics\Logger\HttpStatisticsLogger */
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
