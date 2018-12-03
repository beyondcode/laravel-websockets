<?php

namespace BeyondCode\LaravelWebsockets\Statistics\Events;

use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class StatisticsUpdated implements ShouldBroadcast
{
    use SerializesModels;

    protected $statisticModel;

    public function __construct($statisticModel)
    {
        $this->statisticModel = $statisticModel;
    }

    public function broadcastWith()
    {
        return [
            'time' => $this->statisticModel->created_at->timestamp,
            'app_id' => $this->statisticModel->appId,
            'peak_connection_count' => $this->statisticModel->peakConnectionCount,
            'websocket_message_count' => $this->statisticModel->webSocketMessageCount,
            'api_message_count' => $this->statisticModel->apiMessageCount,
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