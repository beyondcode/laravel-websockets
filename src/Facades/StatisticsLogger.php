<?php

namespace BeyondCode\LaravelWebSockets\Facades;

use BeyondCode\LaravelWebSockets\Statistics\Logger\FakeStatisticsLogger;
use BeyondCode\LaravelWebSockets\Statistics\Logger\StatisticsLogger as StatisticsLoggerInterface;
use Illuminate\Support\Facades\Facade;

/** @see \BeyondCode\LaravelWebSockets\Statistics\Logger\HttpStatisticsLogger */
class StatisticsLogger extends Facade
{
    protected static function getFacadeAccessor()
    {
        return StatisticsLoggerInterface::class;
    }

    public static function fake()
    {
        static::swap(new FakeStatisticsLogger());
    }
}
