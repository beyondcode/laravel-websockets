<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logger;

use BeyondCode\LaravelWebSockets\Statistics\Http\Controllers\WebsocketStatisticsEntriesController;
use BeyondCode\LaravelWebSockets\Statistics\Statistic;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use GuzzleHttp\Client;
use Ratchet\ConnectionInterface;

class HttpStatisticsLogger implements StatisticsLogger
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
        echo 'in actual save method';

        foreach ($this->statistics as $appId => $statistic) {
            echo "stats of ${appId} " . $statistic->isEnabled() ? 'enabled' : 'DISABLED!';
            if (!$statistic->isEnabled()) {
                continue;
            }

            echo 'posted';
            $this->client
                ->postAsync(
                    action([WebsocketStatisticsEntriesController::class, 'store']),
                    $statistic->toArray()
                )
                ->then(function() {
                    echo 'fulfilled';
                })
                ->otherwise(function() {
                    echo 'rejected!';
                    var_dump(func_get_args());
                });

            // Reset connection and message count
            $currentConnectionCount = collect($this->channelManager->getChannels($appId))
                ->sum(function ($channel) {
                    return count($channel->getSubscribedConnections());
                });

            $statistic->reset($currentConnectionCount);
        }
    }
}