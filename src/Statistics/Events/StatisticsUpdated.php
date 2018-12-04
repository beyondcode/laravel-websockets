<?php

namespace BeyondCode\LaravelWebsockets\Statistics\Events;

use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class StatisticsUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public $statisticModel;

    public function __construct($statisticModel)
    {
        $this->statisticModel = $statisticModel;
    }

    public function broadcastWith()
    {
        return [
            'time' => (string)$this->statisticModel->created_at,
            'app_id' => $this->statisticModel->app_id,
            'peak_connection_count' => $this->statisticModel->peak_connection_count,
            'websocket_message_count' => $this->statisticModel->websocket_message_count,
            'api_message_count' => $this->statisticModel->api_message_count,
        ];
    }

    public function broadcastOn()
    {
        return new PrivateChannel(str_after(DashboardLogger::LOG_CHANNEL_PREFIX . 'statistics', 'private-'));
    }

    public function broadcastAs()
    {
        return 'statistics-updated';
    }
}