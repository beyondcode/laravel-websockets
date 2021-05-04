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
     *
     * @return array
     */
    public function __invoke(Request $request, string $appId): array
    {
        return StatisticsStore::getForGraph(
            function ($query) use ($appId) {
                return $query->whereAppId($appId)->latest()->limit(120);
            }, function ($collection) {
                return $collection->reverse();
            }
        );
    }
}
