<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Events;

use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class StatisticsUpdated implements ShouldBroadcast
{
    use SerializesModels;

    /**
     * The statistic instance that got updated.
     *
     * @var \BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry
     */
    protected $webSocketsStatisticsEntry;

    /**
     * Initialize the event.
     *
     * @param  \BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry  $webSocketsStatisticsEntry
     * @return void
     */
    public function __construct(WebSocketsStatisticsEntry $webSocketsStatisticsEntry)
    {
        $this->webSocketsStatisticsEntry = $webSocketsStatisticsEntry;
    }

    /**
     * Format the broadcasting message.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'time' => (string) $this->webSocketsStatisticsEntry->created_at,
            'app_id' => $this->webSocketsStatisticsEntry->app_id,
            'peak_connection_count' => $this->webSocketsStatisticsEntry->peak_connection_count,
            'websocket_message_count' => $this->webSocketsStatisticsEntry->websocket_message_count,
            'api_message_count' => $this->webSocketsStatisticsEntry->api_message_count,
        ];
    }

    /**
     * Specify the channel to broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn()
    {
        $channelName = Str::after(DashboardLogger::LOG_CHANNEL_PREFIX.'statistics', 'private-');

        return new PrivateChannel(
            Str::after(DashboardLogger::LOG_CHANNEL_PREFIX.'statistics', 'private-')
        );
    }

    /**
     * Define the broadcasted event name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'statistics-updated';
    }
}
