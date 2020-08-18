<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Models;

use Illuminate\Database\Eloquent\Model;

class WebSocketsStatisticsEntry extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    protected $table = 'websockets_statistics_entries';
}
