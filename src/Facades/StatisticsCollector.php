<?php

namespace BeyondCode\LaravelWebSockets\Facades;

use BeyondCode\LaravelWebSockets\Contracts\StatisticsCollector as StatisticsCollectorInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void webSocketMessage(string|int $appId)
 * @method static void apiMessage(string|int $appId)
 * @method static void connection(string|int $appId)
 * @method static void disconnection(string|int $appId)
 * @method static void save()
 * @method static void flush()
 * @method static \Amp\Promise getStatistics()
 * @method static \Amp\Promise getAppStatistics(string|int $appId)
 * @method static void resetAppTraces(string|int $appId)
 */
class StatisticsCollector extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return StatisticsCollectorInterface::class;
    }
}
