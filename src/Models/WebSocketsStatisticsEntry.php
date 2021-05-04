<?php

namespace BeyondCode\LaravelWebSockets\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read string app_id
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
     * The name of the "updated at" column.
     *
     * @var string|null
     */
    public const UPDATED_AT = null;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'app_id',
        'peak_connections_count',
        'websocket_messages_count',
        'api_messages_count',
    ];
}
