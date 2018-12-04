<?php

namespace BeyondCode\LaravelWebSockets\Statistics;

use BeyondCode\LaravelWebSockets\Apps\App;

class Statistic
{
    /** @var int|string */
    protected $appId;

    /** @var int */
    protected $currentConnectionCount = 0;

    /** @var int */
    protected $peakConnectionCount = 0;

    /** @var int */
    protected $webSocketMessageCount = 0;

    /** @var int */
    protected $apiMessageCount = 0;

    public function __construct($appId)
    {
        $this->appId = $appId;
    }

    public function isEnabled(): bool
    {
        return App::findById($this->appId)->statisticsEnabled;
    }

    public function connection()
    {
        $this->currentConnectionCount++;

        $this->peakConnectionCount = max($this->currentConnectionCount, $this->peakConnectionCount);
    }

    public function disconnection()
    {
        $this->currentConnectionCount--;

        $this->peakConnectionCount = max($this->currentConnectionCount, $this->peakConnectionCount);
    }

    public function webSocketMessage()
    {
        $this->webSocketMessageCount++;
    }

    public function apiMessage()
    {
        $this->apiMessageCount++;
    }

    public function reset(int $currentConnectionCount)
    {
        $this->currentConnectionCount = $currentConnectionCount;
        $this->peakConnectionCount = $currentConnectionCount;
        $this->webSocketMessageCount = 0;
        $this->apiMessageCount = 0;
    }

    public function toArray()
    {
        return [
            'app_id' => $this->appId,
            'peak_connection_count' => $this->peakConnectionCount,
            'websocket_message_count' => $this->webSocketMessageCount,
            'api_message_count' => $this->apiMessageCount,
        ];
    }
}
