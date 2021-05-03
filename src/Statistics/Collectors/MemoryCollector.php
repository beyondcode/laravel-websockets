<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Collectors;

use Amp\Promise;
use BeyondCode\LaravelWebSockets\Contracts\ChannelManager;
use BeyondCode\LaravelWebSockets\Contracts\StatisticsCollector;
use BeyondCode\LaravelWebSockets\Facades\StatisticsStore;
use BeyondCode\LaravelWebSockets\Helpers;
use BeyondCode\LaravelWebSockets\Statistics\Statistic;

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
    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    /**
     * Handle the incoming websocket message.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function webSocketMessage($appId): void
    {
        $this->findOrMake($appId)->webSocketMessage();
    }

    /**
     * Handle the incoming API message.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function apiMessage($appId): void
    {
        $this->findOrMake($appId)->apiMessage();
    }

    /**
     * Handle the new connection.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function connection($appId): void
    {
        $this->findOrMake($appId)->connection();
    }

    /**
     * Handle disconnections.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function disconnection($appId): void
    {
        $this->findOrMake($appId)
            ->disconnection();
    }

    /**
     * Save all the stored statistics.
     *
     * @return void
     */
    public function save(): void
    {
        $this->getStatistics()->onResolve(function ($error, $statistics): void {
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
                    ->onResolve(static function (?int $connections) use ($statistic): void {
                        $statistic->reset((int)$connections);
                    });
            }
        });
    }

    /**
     * Flush the stored statistics.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->statistics = [];
    }

    /**
     * Get the saved statistics.
     *
     * @return \Amp\Promise
     */
    public function getStatistics(): Promise
    {
        return Helpers::createFulfilledPromise($this->statistics);
    }

    /**
     * Get the saved statistics for an app.
     *
     * @param  string|int  $appId
     * @return \Amp\Promise
     */
    public function getAppStatistics($appId): Promise
    {
        return Helpers::createFulfilledPromise($this->statistics[$appId] ?? null);
    }

    /**
     * Remove all app traces from the database if no connections have been set
     * in the meanwhile since last save.
     *
     * @param  string|int  $appId
     * @return void
     */
    public function resetAppTraces($appId): void
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
    public function createRecord(Statistic $statistic, $appId): void
    {
        StatisticsStore::store($statistic->toArray());
    }
}
