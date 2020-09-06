<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logger;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;
use BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Facades\Redis;

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
     * The replicator client.
     *
     * @var ReplicationInterface
     */
    protected $replicator;

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
        $this->replicator = app(ReplicationInterface::class);

        $this->redis = Redis::connection(
            config('websockets.replication.redis.connection', 'default')
        );
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
        // Increment the current connections count by 1.
        $incremented = $this->ensureAppIsSet($appId)
            ->hincrby($this->getHash($appId), 'current_connection_count', 1);

        $incremented->then(function ($currentConnectionCount) use ($appId) {
            // Get the peak connections count from Redis.
            $peakConnectionCount = $this->replicator
                ->getPublishClient()
                ->hget($this->getHash($appId), 'peak_connection_count');

            $peakConnectionCount->then(function ($currentPeakConnectionCount) use ($currentConnectionCount, $appId) {
                // Extract the greatest number between the current peak connection count
                // and the current connection number.

                $peakConnectionCount = is_null($currentPeakConnectionCount)
                    ? $currentConnectionCount
                    : max($currentPeakConnectionCount, $currentConnectionCount);

                // Then set it to the database.
                $this->replicator
                    ->getPublishClient()
                    ->hset($this->getHash($appId), 'peak_connection_count', $peakConnectionCount);
            });
        });
    }

    /**
     * Handle disconnections.
     *
     * @param  mixed  $appId
     * @return void
     */
    public function disconnection($appId)
    {
        // Decrement the current connections count by 1.
        $decremented = $this->ensureAppIsSet($appId)
            ->hincrby($this->getHash($appId), 'current_connection_count', -1);

        $decremented->then(function ($currentConnectionCount) use ($appId) {
            // Get the peak connections count from Redis.
            $peakConnectionCount = $this->replicator
                ->getPublishClient()
                ->hget($this->getHash($appId), 'peak_connection_count');

            $peakConnectionCount->then(function ($currentPeakConnectionCount) use ($currentConnectionCount, $appId) {
                // Extract the greatest number between the current peak connection count
                // and the current connection number.

                $peakConnectionCount = is_null($currentPeakConnectionCount)
                    ? $currentConnectionCount
                    : max($currentPeakConnectionCount, $currentConnectionCount);

                // Then set it to the database.
                $this->replicator
                    ->getPublishClient()
                    ->hset($this->getHash($appId), 'peak_connection_count', $peakConnectionCount);
            });
        });
    }

    /**
     * Save all the stored statistics.
     *
     * @return void
     */
    public function save()
    {
        $this->lock()->get(function () {
            $setMembers = $this->replicator
                ->getPublishClient()
                ->smembers('laravel-websockets:apps');

            $setMembers->then(function ($members) {
                foreach ($members as $appId) {
                    $member = $this->replicator
                        ->getPublishClient()
                        ->hgetall($this->getHash($appId));

                    $member->then(function ($statistic) use ($appId) {
                        if (! $statistic) {
                            return;
                        }

                        // Statistics come into a list where the keys are on even indexes
                        // and the values are on odd indexes. This way, we know which
                        // ones are keys and which ones are values and their get combined
                        // later to form the key => value array

                        [$keys, $values] = collect($statistic)->partition(function ($value, $key) {
                            return $key % 2 === 0;
                        });

                        $statistic = array_combine($keys->all(), $values->all());

                        $this->createRecord($statistic, $appId);

                        $this->channelManager
                            ->getGlobalConnectionsCount($appId)
                            ->then(function ($currentConnectionCount) use ($appId) {
                                $currentConnectionCount === 0 || is_null($currentConnectionCount)
                                    ? $this->resetAppTraces($appId)
                                    : $this->resetStatistics($appId, $currentConnectionCount);
                            });
                    });
                }
            });
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
        $this->replicator
            ->getPublishClient()
            ->sadd('laravel-websockets:apps', $appId);

        return $this->replicator->getPublishClient();
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
        $this->replicator
            ->getPublishClient()
            ->hset($this->getHash($appId), 'current_connection_count', $currentConnectionCount);

        $this->replicator
            ->getPublishClient()
            ->hset($this->getHash($appId), 'peak_connection_count', $currentConnectionCount);

        $this->replicator
            ->getPublishClient()
            ->hset($this->getHash($appId), 'websocket_message_count', 0);

        $this->replicator
            ->getPublishClient()
            ->hset($this->getHash($appId), 'api_message_count', 0);
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
        $this->replicator
            ->getPublishClient()
            ->hdel($this->getHash($appId), 'current_connection_count');

        $this->replicator
            ->getPublishClient()
            ->hdel($this->getHash($appId), 'peak_connection_count');

        $this->replicator
            ->getPublishClient()
            ->hdel($this->getHash($appId), 'websocket_message_count');

        $this->replicator
            ->getPublishClient()
            ->hdel($this->getHash($appId), 'api_message_count');

        $this->replicator
            ->getPublishClient()
            ->srem('laravel-websockets:apps', $appId);
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
