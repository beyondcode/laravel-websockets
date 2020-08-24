<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logger;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver;
use BeyondCode\LaravelWebSockets\Statistics\Statistic;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Ratchet\ConnectionInterface;

class MemoryStatisticsLogger implements StatisticsLogger
{
    /**
     * The list of stored statistics.
     *
     * @var array
     */
    protected $statistics = [];

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
    }

    /**
     * Handle the incoming websocket message.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function webSocketMessage(ConnectionInterface $connection)
    {
        $this->findOrMakeStatisticForAppId($connection->app->id)
            ->webSocketMessage();
    }

    /**
     * Handle the incoming API message.
     *
     * @param  mixed  $appId
     * @return void
     */
    public function apiMessage($appId)
    {
        $this->findOrMakeStatisticForAppId($appId)
            ->apiMessage();
    }

    /**
     * Handle the new conection.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function connection(ConnectionInterface $connection)
    {
        $this->findOrMakeStatisticForAppId($connection->app->id)
            ->connection();
    }

    /**
     * Handle disconnections.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function disconnection(ConnectionInterface $connection)
    {
        $this->findOrMakeStatisticForAppId($connection->app->id)
            ->disconnection();
    }

    /**
     * Save all the stored statistics.
     *
     * @return void
     */
    public function save()
    {
        foreach ($this->statistics as $appId => $statistic) {
            if (! $statistic->isEnabled()) {
                continue;
            }

            $this->driver::create($statistic->toArray());

            $currentConnectionCount = $this->channelManager->getConnectionCount($appId);

            $statistic->reset($currentConnectionCount);
        }
    }

    /**
     * Find or create a defined statistic for an app.
     *
     * @param  mixed  $appId
     * @return Statistic
     */
    protected function findOrMakeStatisticForAppId($appId): Statistic
    {
        if (! isset($this->statistics[$appId])) {
            $this->statistics[$appId] = new Statistic($appId);
        }

        return $this->statistics[$appId];
    }
}
