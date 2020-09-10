<?php

namespace BeyondCode\LaravelWebSockets\Facades;

use Illuminate\Support\Facades\Facade;
use BeyondCode\LaravelWebSockets\Contracts\StatisticsCollector as StatisticsCollectorInterface;

class StatisticsCollector extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return StatisticsCollectorInterface::class;
    }
}
