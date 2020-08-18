<?php

namespace BeyondCode\LaravelWebSockets\Statistics;

use BeyondCode\LaravelWebSockets\Apps\App;

class Statistic
{
    /**
     * The app id.
     *
     * @var mixed
     */
    protected $appId;

    /**
     * The current connections count ticker.
     *
     * @var int
     */
    protected $currentConnectionCount = 0;

    /**
     * The peak connections count ticker.
     *
     * @var int
     */
    protected $peakConnectionCount = 0;

    /**
     * The websockets connections count ticker.
     *
     * @var int
     */
    protected $webSocketMessageCount = 0;

    /**
     * The api messages connections count ticker.
     *
     * @var int
     */
    protected $apiMessageCount = 0;

    /**
     * Create a new statistic.
     *
     * @param  mixed  $appId
     * @return  void
     */
    public function __construct($appId)
    {
        $this->appId = $appId;
    }

    /**
     * Check if the app has statistics enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return App::findById($this->appId)->statisticsEnabled;
    }

    /**
     * Handle a new connection increment.
     *
     * @return void
     */
    public function connection()
    {
        $this->currentConnectionCount++;

        $this->peakConnectionCount = max($this->currentConnectionCount, $this->peakConnectionCount);
    }

    /**
     * Handle a disconnection decrement.
     *
     * @return void
     */
    public function disconnection()
    {
        $this->currentConnectionCount--;

        $this->peakConnectionCount = max($this->currentConnectionCount, $this->peakConnectionCount);
    }

    /**
     * Handle a new websocket message.
     *
     * @return void
     */
    public function webSocketMessage()
    {
        $this->webSocketMessageCount++;
    }

    /**
     * Handle a new api message.
     *
     * @return void
     */
    public function apiMessage()
    {
        $this->apiMessageCount++;
    }

    /**
     * Reset all the connections to a specific count.
     *
     * @param  int  $currentConnectionCount
     * @return void
     */
    public function reset(int $currentConnectionCount)
    {
        $this->currentConnectionCount = $currentConnectionCount;
        $this->peakConnectionCount = $currentConnectionCount;
        $this->webSocketMessageCount = 0;
        $this->apiMessageCount = 0;
    }

    /**
     * Transform the statistic to array.
     *
     * @return array
     */
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
