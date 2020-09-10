<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Collectors;

use BeyondCode\LaravelWebSockets\Statistics\Statistic;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Facades\Redis;
use React\Promise\PromiseInterface;

class RedisCollector extends MemoryCollector
{
    /**
     * The Redis manager instance.
     *
     * @var \Illuminate\Redis\RedisManager
     */
    protected $redis;

    /**
     * The set name for the Redis storage.
     *
     * @var string
     */
    protected static $redisSetName = 'laravel-websockets:apps';

    /**
     * The lock name to use on Redis to avoid multiple
     * collector-to-store actions that may result
     * in multiple data points set to the store.
     *
     * @var string
     */
    protected static $redisLockName = 'laravel-websockets:lock';

    /**
     * Initialize the logger.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->redis = Redis::connection(
            config('websockets.replication.modes.redis.connection', 'default')
        );
    }

    /**
     * Handle the incoming websocket message.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function webSocketMessage($appId)
    {
        $this->ensureAppIsInSet($appId)
            ->hincrby(
                $this->channelManager->getRedisKey($appId, null, ['stats']),
                'websocket_messages_count', 1
            );
    }

    /**
     * Handle the incoming API message.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function apiMessage($appId)
    {
        $this->ensureAppIsInSet($appId)
            ->hincrby(
                $this->channelManager->getRedisKey($appId, null, ['stats']),
                'api_messages_count', 1
            );
    }

    /**
     * Handle the new conection.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function connection($appId)
    {
        // Increment the current connections count by 1.
        $this->ensureAppIsInSet($appId)
            ->hincrby(
                $this->channelManager->getRedisKey($appId, null, ['stats']),
                'current_connections_count', 1
            )
            ->then(function ($currentConnectionsCount) use ($appId) {
                // Get the peak connections count from Redis.
                $this->channelManager
                    ->getPublishClient()
                    ->hget(
                        $this->channelManager->getRedisKey($appId, null, ['stats']),
                        'peak_connections_count'
                    )
                    ->then(function ($currentPeakConnectionCount) use ($currentConnectionsCount, $appId) {
                        // Extract the greatest number between the current peak connection count
                        // and the current connection number.
                        $peakConnectionsCount = is_null($currentPeakConnectionCount)
                            ? $currentConnectionsCount
                            : max($currentPeakConnectionCount, $currentConnectionsCount);

                        // Then set it to the database.
                        $this->channelManager
                            ->getPublishClient()
                            ->hset(
                                $this->channelManager->getRedisKey($appId, null, ['stats']),
                                'peak_connections_count', $peakConnectionsCount
                            );
                    });
            });
    }

    /**
     * Handle disconnections.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function disconnection($appId)
    {
        // Decrement the current connections count by 1.
        $this->ensureAppIsInSet($appId)
            ->hincrby(
                $this->channelManager->getRedisKey($appId, null, ['stats']),
                'current_connections_count', -1
            )
            ->then(function ($currentConnectionsCount) use ($appId) {
                // Get the peak connections count from Redis.
                $this->channelManager
                    ->getPublishClient()
                    ->hget(
                        $this->channelManager->getRedisKey($appId, null, ['stats']),
                        'peak_connections_count'
                    )
                    ->then(function ($currentPeakConnectionCount) use ($currentConnectionsCount, $appId) {
                        // Extract the greatest number between the current peak connection count
                        // and the current connection number.
                        $peakConnectionsCount = is_null($currentPeakConnectionCount)
                            ? $currentConnectionsCount
                            : max($currentPeakConnectionCount, $currentConnectionsCount);

                        // Then set it to the database.
                        $this->channelManager
                            ->getPublishClient()
                            ->hset(
                                $this->channelManager->getRedisKey($appId, null, ['stats']),
                                'peak_connections_count', $peakConnectionsCount
                            );
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
            $this->channelManager
                ->getPublishClient()
                ->smembers(static::$redisSetName)
                ->then(function ($members) {
                    foreach ($members as $appId) {
                        $this->channelManager
                            ->getPublishClient()
                            ->hgetall($this->channelManager->getRedisKey($appId, null, ['stats']))
                            ->then(function ($list) use ($appId) {
                                if (! $list) {
                                    return;
                                }

                                $statistic = $this->listToStatisticInstance(
                                    $appId, $list
                                );

                                $this->createRecord($statistic, $appId);

                                $this->channelManager
                                    ->getGlobalConnectionsCount($appId)
                                    ->then(function ($currentConnectionsCount) use ($appId) {
                                        $currentConnectionsCount === 0 || is_null($currentConnectionsCount)
                                            ? $this->resetAppTraces($appId)
                                            : $this->resetStatistics($appId, $currentConnectionsCount);
                                    });
                            });
                    }
                });
        });
    }

    /**
     * Flush the stored statistics.
     *
     * @return void
     */
    public function flush()
    {
        $this->getStatistics()->then(function ($statistics) {
            foreach ($statistics as $appId => $statistic) {
                $this->resetAppTraces($appId);
            }
        });
    }

    /**
     * Get the saved statistics.
     *
     * @return PromiseInterface[array]
     */
    public function getStatistics(): PromiseInterface
    {
        return $this->channelManager
            ->getPublishClient()
            ->smembers(static::$redisSetName)
            ->then(function ($members) use (&$statistics) {
                $appsWithStatistics = [];

                foreach ($members as $appId) {
                    $this->channelManager
                        ->getPublishClient()
                        ->hgetall($this->channelManager->getRedisKey($appId, null, ['stats']))
                        ->then(function ($list) use ($appId, &$appsWithStatistics) {
                            $appsWithStatistics[$appId] = $this->listToStatisticInstance(
                                $appId, $list
                            );
                        });
                }

                return $appsWithStatistics;
            });
    }

    /**
     * Get the saved statistics for an app.
     *
     * @param  string|int  $appId
     * @return PromiseInterface[\BeyondCode\LaravelWebSockets\Statistics\Statistic|null]
     */
    public function getAppStatistics($appId): PromiseInterface
    {
        return $this->channelManager
            ->getPublishClient()
            ->hgetall($this->channelManager->getRedisKey($appId, null, ['stats']))
            ->then(function ($list) use ($appId, &$appStatistics) {
                return $this->listToStatisticInstance(
                    $appId, $list
                );
            });
    }

    /**
     * Reset the statistics to a specific connection count.
     *
     * @param  string|int  $appId
     * @param  int  $currentConnectionCount
     * @return void
     */
    public function resetStatistics($appId, int $currentConnectionCount)
    {
        $this->channelManager
            ->getPublishClient()
            ->hset(
                $this->channelManager->getRedisKey($appId, null, ['stats']),
                'current_connections_count', $currentConnectionCount
            );

        $this->channelManager
            ->getPublishClient()
            ->hset(
                $this->channelManager->getRedisKey($appId, null, ['stats']),
                'peak_connections_count', $currentConnectionCount
            );

        $this->channelManager
            ->getPublishClient()
            ->hset(
                $this->channelManager->getRedisKey($appId, null, ['stats']),
                'websocket_messages_count', 0
            );

        $this->channelManager
            ->getPublishClient()
            ->hset(
                $this->channelManager->getRedisKey($appId, null, ['stats']),
                'api_messages_count', 0
            );
    }

    /**
     * Remove all app traces from the database if no connections have been set
     * in the meanwhile since last save.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function resetAppTraces($appId)
    {
        $this->channelManager
            ->getPublishClient()
            ->hdel(
                $this->channelManager->getRedisKey($appId, null, ['stats']),
                'current_connections_count'
            );

        $this->channelManager
            ->getPublishClient()
            ->hdel(
                $this->channelManager->getRedisKey($appId, null, ['stats']),
                'peak_connections_count'
            );

        $this->channelManager
            ->getPublishClient()
            ->hdel(
                $this->channelManager->getRedisKey($appId, null, ['stats']),
                'websocket_messages_count'
            );

        $this->channelManager
            ->getPublishClient()
            ->hdel(
                $this->channelManager->getRedisKey($appId, null, ['stats']),
                'api_messages_count'
            );

        $this->channelManager
            ->getPublishClient()
            ->srem(static::$redisSetName, $appId);
    }

    /**
     * Ensure the app id is stored in the Redis database.
     *
     * @param  string|int  $appId
     * @return \Clue\React\Redis\Client
     */
    protected function ensureAppIsInSet($appId)
    {
        $this->channelManager
            ->getPublishClient()
            ->sadd(static::$redisSetName, $appId);

        return $this->channelManager->getPublishClient();
    }

    /**
     * Get a new RedisLock instance to avoid race conditions.
     *
     * @return \Illuminate\Cache\CacheLock
     */
    protected function lock()
    {
        return new RedisLock($this->redis, static::$redisLockName, 0);
    }

    /**
     * Transform the Redis' list of key after value
     * to key-value pairs.
     *
     * @param  array  $list
     * @return array
     */
    protected function listToKeyValue(array $list)
    {
        // Redis lists come into a format where the keys are on even indexes
        // and the values are on odd indexes. This way, we know which
        // ones are keys and which ones are values and their get combined
        // later to form the key => value array.

        [$keys, $values] = collect($list)->partition(function ($value, $key) {
            return $key % 2 === 0;
        });

        return array_combine($keys->all(), $values->all());
    }

    /**
     * Transform a list coming from a Redis list
     * to a Statistic instance.
     *
     * @param  string|int  $appId
     * @param  array  $list
     * @return \BeyondCode\LaravelWebSockets\Statistics\Statistic
     */
    protected function listToStatisticInstance($appId, array $list)
    {
        $list = $this->listToKeyValue($list);

        return (new Statistic($appId))
            ->setCurrentConnectionsCount($list['current_connections_count'] ?? 0)
            ->setPeakConnectionsCount($list['peak_connections_count'] ?? 0)
            ->setWebSocketMessagesCount($list['websocket_messages_count'] ?? 0)
            ->setApiMessagesCount($list['api_messages_count'] ?? 0);
    }
}
