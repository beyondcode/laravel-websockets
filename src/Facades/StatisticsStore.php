<?php

namespace BeyondCode\LaravelWebSockets\Facades;

use Illuminate\Support\Facades\Facade;
use BeyondCode\LaravelWebSockets\Contracts\StatisticsStore as StatisticsStoreInterface;

class StatisticsStore extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return StatisticsStoreInterface::class;
    }
}
