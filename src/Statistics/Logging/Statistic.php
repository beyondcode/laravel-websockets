<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logging;

class Statistic
{
    protected $appId;

    /** @var int */
    protected $connections = 0;

    /** @var int */
    protected $peakConnections = 0;

    /** @var int */
    protected $webSocketMessageCount = 0;

    /** @var int */
    protected $apiMessageCount = 0;

    public function __construct($appId)
    {
        $this->appId = $appId;
    }

    public function connection()
    {
        $this->connections++;

        $this->peakConnections = max($this->connections, $this->peakConnections);
    }

    public function disconnection()
    {
        $this->connections--;

        $this->peakConnections = max($this->connections, $this->peakConnections);
    }

    public function webSocketMessage()
    {
        $this->webSocketMessageCount++;
    }

    public function apiMessage()
    {
        $this->apiMessageCount++;
    }

    public function reset(int $connections)
    {
        $this->connections = $connections;
        $this->peakConnections = $connections;
        $this->webSocketMessageCount = 0;
        $this->apiMessageCount = 0;
    }

    public function toArray()
    {
        return [
            'app_id' => $this->appId,
            'peak_connections' => $this->peakConnections,
            'websocket_message_count' => $this->webSocketMessageCount,
            'api_message_count' => $this->apiMessageCount,
        ];
    }
}