<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Models;

use Illuminate\Database\Eloquent\Model;

class WebSocketsStatisticsEntry extends Model
{
    protected $guarded = [];

    public function getTable()
    {
        return config('websockets.database.tables.statistics_entries');
    }
}
