<?php

namespace BeyondCode\LaravelWebSockets\Models;

use Illuminate\Database\Eloquent\Model;

class WebSocketsStatisticsEntry extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'websockets_statistics_entries';

    /**
     * {@inheritdoc}
     */
    protected $guarded = [];
}
