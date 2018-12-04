<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Models;

use Illuminate\Database\Eloquent\Model;

class WebSocketsStatisticsEntry extends Model
{
    protected $guarded = [];

    protected $table = 'websockets_statistics_entries';
}
