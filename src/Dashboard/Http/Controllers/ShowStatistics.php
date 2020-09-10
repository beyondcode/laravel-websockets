<?php

namespace BeyondCode\LaravelWebSockets\Dashboard\Http\Controllers;

use BeyondCode\LaravelWebSockets\Facades\StatisticsStore;
use Illuminate\Http\Request;

class ShowStatistics
{
    /**
     * Get statistics for an app ID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $appId
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, $appId)
    {
        $processQuery = function ($query) use ($appId) {
            return $query->whereAppId($appId)
                ->latest()
                ->limit(120);
        };

        $processCollection = function ($collection) {
            return $collection->reverse();
        };

        return StatisticsStore::getForGraph(
            $processQuery, $processCollection
        );
    }
}
