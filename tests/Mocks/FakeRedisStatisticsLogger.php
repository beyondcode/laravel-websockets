<?php

namespace BeyondCode\LaravelWebSockets\Tests\Mocks;

use BeyondCode\LaravelWebSockets\Statistics\Logger\RedisStatisticsLogger;

class FakeRedisStatisticsLogger extends RedisStatisticsLogger
{
    /**
     * Get app by id.
     *
     * @param  mixed  $appId
     * @return array
     */
    public function getForAppId($appId): array
    {
        return [
            'app_id' => $appId,
            'peak_connection_count' => $this->redis->hget($this->getHash($appId), 'peak_connection_count') ?: 0,
            'websocket_message_count' => $this->redis->hget($this->getHash($appId), 'websocket_message_count') ?: 0,
            'api_message_count' => $this->redis->hget($this->getHash($appId), 'api_message_count') ?: 0,
        ];
    }
}
