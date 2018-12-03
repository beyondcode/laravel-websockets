<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logging;

use BeyondCode\LaravelWebSockets\Statistics\Http\Controllers\WebsocketStatisticsEntriesController;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Ratchet\ConnectionInterface;

class StatisticsLogger
{
    /** @var Statistic[] */
    protected $statistics = [];

    /** @var \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager */
    protected $channelManager;

    public function __construct(ChannelManager $channelManager, Client $client)
    {
        $this->channelManager = $channelManager;

        $this->client = $client;
    }

    public function webSocketMessage(ConnectionInterface $connection)
    {
        $this->initializeStatistics($connection->app->id);

        $this->statistics[$connection->app->id]->webSocketMessage();
    }

    public function apiMessage($appId)
    {
        $this->initializeStatistics($appId);

        $this->statistics[$appId]->apiMessage();
    }

    public function connection(ConnectionInterface $connection)
    {
        $this->initializeStatistics($connection->app->id);

        $this->statistics[$connection->app->id]->connection();
    }

    public function disconnection(ConnectionInterface $connection)
    {
        $this->initializeStatistics($connection->app->id);

        $this->statistics[$connection->app->id]->disconnection();
    }

    protected function initializeStatistics($id)
    {
        if (!isset($this->statistics[$id])) {
            $this->statistics[$id] = new Statistic($id);
        }
    }

    public function save()
    {
        foreach ($this->statistics as $appId => $statistic) {
            if (! $statistic->isEnabled()) {
                continue;
            }

            $this->client->postAsync(
                action([WebsocketStatisticsEntriesController::class, 'store']),
                $statistic->toArray()
            );

            // Reset connection and message count
            $currentConnectionCount = collect($this->channelManager->getChannels($appId))
                ->sum(function ($channel) {
                    return count($channel->getSubscribedConnections());
                });

            $statistic->reset($currentConnectionCount);
        }
    }
}