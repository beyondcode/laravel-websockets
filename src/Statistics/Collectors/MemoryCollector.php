<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Collectors;

use BeyondCode\LaravelWebSockets\Contracts\ChannelManager;
use BeyondCode\LaravelWebSockets\Contracts\StatisticsCollector;
use BeyondCode\LaravelWebSockets\Facades\StatisticsStore;
use BeyondCode\LaravelWebSockets\Helpers;
use BeyondCode\LaravelWebSockets\Statistics\Statistic;
use React\Promise\PromiseInterface;

class MemoryCollector implements StatisticsCollector
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
     * @var \BeyondCode\LaravelWebSockets\Contracts\ChannelManager
     */
    protected $channelManager;

    /**
     * Initialize the logger.
     *
     * @return void
     */
    public function __construct()
    {
        $this->channelManager = app(ChannelManager::class);
    }

    /**
     * Handle the incoming websocket message.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function webSocketMessage($appId)
    {
        $this->findOrMake($appId)
            ->webSocketMessage();
    }

    /**
     * Handle the incoming API message.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function apiMessage($appId)
    {
        $this->findOrMake($appId)
            ->apiMessage();
    }

    /**
     * Handle the new conection.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function connection($appId)
    {
        $this->findOrMake($appId)
            ->connection();
    }

    /**
     * Handle disconnections.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function disconnection($appId)
    {
        $this->findOrMake($appId)
            ->disconnection();
    }

    /**
     * Save all the stored statistics.
     *
     * @return void
     */
    public function save()
    {
        $this->getStatistics()->then(function ($statistics) {
            foreach ($statistics as $appId => $statistic) {
                if (! $statistic->isEnabled()) {
                    continue;
                }

                if ($statistic->shouldHaveTracesRemoved()) {
                    $this->resetAppTraces($appId);

                    continue;
                }

                $this->createRecord($statistic, $appId);

                $this->channelManager
                    ->getGlobalConnectionsCount($appId)
                    ->then(function ($connections) use ($statistic) {
                        $statistic->reset(
                            is_null($connections) ? 0 : $connections
                        );
                    });
            }
        });
    }

    /**
     * Flush the stored statistics.
     *
     * @return void
     */
    public function flush()
    {
        $this->statistics = [];
    }

    /**
     * Get the saved statistics.
     *
     * @return PromiseInterface[array]
     */
    public function getStatistics(): PromiseInterface
    {
        return Helpers::createFulfilledPromise($this->statistics);
    }

    /**
     * Get the saved statistics for an app.
     *
     * @param  string|int  $appId
     * @return PromiseInterface[\BeyondCode\LaravelWebSockets\Statistics\Statistic|null]
     */
    public function getAppStatistics($appId): PromiseInterface
    {
        return Helpers::createFulfilledPromise(
            $this->statistics[$appId] ?? null
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
        unset($this->statistics[$appId]);
    }

    /**
     * Find or create a defined statistic for an app.
     *
     * @param  string|int  $appId
     * @return \BeyondCode\LaravelWebSockets\Statistics\Statistic
     */
    protected function findOrMake($appId): Statistic
    {
        if (! isset($this->statistics[$appId])) {
            $this->statistics[$appId] = Statistic::new($appId);
        }

        return $this->statistics[$appId];
    }

    /**
     * Create a new record using the Statistic Store.
     *
     * @param  \BeyondCode\LaravelWebSockets\Statistics\Statistic  $statistic
     * @param  mixed  $appId
     * @return void
     */
    public function createRecord(Statistic $statistic, $appId)
    {
        StatisticsStore::store($statistic->toArray());
    }
}
