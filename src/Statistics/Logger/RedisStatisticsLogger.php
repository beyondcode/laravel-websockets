<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logger;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Facades\Cache;

class RedisStatisticsLogger implements StatisticsLogger
{
    /**
     * The Channel manager.
     *
     * @var \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager
     */
    protected $channelManager;

    /**
     * The statistics driver instance.
     *
     * @var \BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver
     */
    protected $driver;

    /**
     * The Redis manager instance.
     *
     * @var \Illuminate\Redis\RedisManager
     */
    protected $redis;

    /**
     * Initialize the logger.
     *
     * @param  \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager  $channelManager
     * @param  \BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver  $driver
     * @return void
     */
    public function __construct(ChannelManager $channelManager, StatisticsDriver $driver)
    {
        $this->channelManager = $channelManager;
        $this->driver = $driver;
        $this->redis = Cache::getRedis();
    }

    /**
     * Handle the incoming websocket message.
     *
     * @param  mixed  $appId
     * @return void
     */
    public function webSocketMessage($appId)
    {
        $this->ensureAppIsSet($appId)
            ->hincrby($this->getHash($appId), 'websocket_message_count', 1);
    }

    /**
     * Handle the incoming API message.
     *
     * @param  mixed  $appId
     * @return void
     */
    public function apiMessage($appId)
    {
        $this->ensureAppIsSet($appId)
            ->hincrby($this->getHash($appId), 'api_message_count', 1);
    }

    /**
     * Handle the new conection.
     *
     * @param  mixed  $appId
     * @return void
     */
    public function connection($appId)
    {
        $currentConnectionCount = $this->ensureAppIsSet($appId)
            ->hincrby($this->getHash($appId), 'current_connection_count', 1);

        $currentPeakConnectionCount = $this->redis->hget($this->getHash($appId), 'peak_connection_count');

        $peakConnectionCount = is_null($currentPeakConnectionCount)
            ? $currentConnectionCount
            : max($currentPeakConnectionCount, $currentConnectionCount);

        $this->redis->hset($this->getHash($appId), 'peak_connection_count', $peakConnectionCount);
    }

    /**
     * Handle disconnections.
     *
     * @param  mixed  $appId
     * @return void
     */
    public function disconnection($appId)
    {
        $currentConnectionCount = $this->ensureAppIsSet($appId)
            ->hincrby($this->getHash($appId), 'current_connection_count', -1);

        $currentPeakConnectionCount = $this->redis->hget($this->getHash($appId), 'peak_connection_count');

        $peakConnectionCount = is_null($currentPeakConnectionCount)
            ? $currentConnectionCount
            : max($currentPeakConnectionCount, $currentConnectionCount);

        $this->redis->hset($this->getHash($appId), 'peak_connection_count', $peakConnectionCount);
    }

    /**
     * Save all the stored statistics.
     *
     * @return void
     */
    public function save()
    {
        $this->lock()->get(function () {
            foreach ($this->redis->smembers('laravel-websockets:apps') as $appId) {
                if (! $statistic = $this->redis->hgetall($this->getHash($appId))) {
                    continue;
                }

                $this->createRecord($statistic, $appId);

                $currentConnectionCount = $this->channelManager->getConnectionCount($appId);

                $currentConnectionCount === 0
                    ? $this->resetAppTraces($appId)
                    : $this->resetStatistics($appId, $currentConnectionCount);
            }
        });
    }

    /**
     * Ensure the app id is stored in the Redis database.
     *
     * @param  mixed  $appId
     * @return \Illuminate\Redis\RedisManager
     */
    protected function ensureAppIsSet($appId)
    {
        $this->redis->sadd('laravel-websockets:apps', $appId);

        return $this->redis;
    }

    /**
     * Reset the statistics to a specific connection count.
     *
     * @param  mixed  $appId
     * @param  int  $currentConnectionCount
     * @return void
     */
    public function resetStatistics($appId, int $currentConnectionCount)
    {
        $this->redis->hset($this->getHash($appId), 'current_connection_count', $currentConnectionCount);
        $this->redis->hset($this->getHash($appId), 'peak_connection_count', $currentConnectionCount);
        $this->redis->hset($this->getHash($appId), 'websocket_message_count', 0);
        $this->redis->hset($this->getHash($appId), 'api_message_count', 0);
    }

    /**
     * Remove all app traces from the database if no connections have been set
     * in the meanwhile since last save.
     *
     * @param  mixed  $appId
     * @return void
     */
    public function resetAppTraces($appId)
    {
        $this->redis->hdel($this->getHash($appId), 'current_connection_count');
        $this->redis->hdel($this->getHash($appId), 'peak_connection_count');
        $this->redis->hdel($this->getHash($appId), 'websocket_message_count');
        $this->redis->hdel($this->getHash($appId), 'api_message_count');

        $this->redis->srem('laravel-websockets:apps', $appId);
    }

    /**
     * Get the Redis hash name for the app.
     *
     * @param  mixed  $appId
     * @return string
     */
    protected function getHash($appId): string
    {
        return "laravel-websockets:app:{$appId}";
    }

    /**
     * Get a new RedisLock instance to avoid race conditions.
     *
     * @return \Illuminate\Cache\CacheLock
     */
    protected function lock()
    {
        return new RedisLock($this->redis, 'laravel-websockets:lock', 0);
    }

    /**
     * Create a new record using the Statistic Driver.
     *
     * @param  array  $statistic
     * @param  mixed  $appId
     * @return void
     */
    protected function createRecord(array $statistic, $appId): void
    {
        $this->driver::create([
            'app_id' => $appId,
            'peak_connection_count' => $statistic['peak_connection_count'] ?? 0,
            'websocket_message_count' => $statistic['websocket_message_count'] ?? 0,
            'api_message_count' => $statistic['api_message_count'] ?? 0,
        ]);
    }
}
