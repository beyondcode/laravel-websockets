<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logger;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver;
use BeyondCode\LaravelWebSockets\Statistics\Statistic;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;

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
     * @param  mixed  $appId
     * @return void
     */
    public function webSocketMessage($appId)
    {
        $this->findOrMakeStatisticForAppId($appId)
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
     * @param  mixed  $appId
     * @return void
     */
    public function connection($appId)
    {
        $this->findOrMakeStatisticForAppId($appId)
            ->connection();
    }

    /**
     * Handle disconnections.
     *
     * @param  mixed  $appId
     * @return void
     */
    public function disconnection($appId)
    {
        $this->findOrMakeStatisticForAppId($appId)
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

            $this->createRecord($statistic, $appId);

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

    /**
     * Get the saved statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return $this->statistics;
    }

    /**
     * Create a new record using the Statistic Driver.
     *
     * @param  Statistic  $statistic
     * @param  mixed  $appId
     * @return void
     */
    public function createRecord(Statistic $statistic, $appId)
    {
        $this->driver::create($statistic->toArray());
    }
}
