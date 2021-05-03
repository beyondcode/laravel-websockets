<?php

namespace BeyondCode\LaravelWebSockets\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int peak_connections_count
 * @property-read int websocket_messages_count
 * @property-read int api_messages_count
 * @property-read \Illuminate\Support\Carbon created_at
 */
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
