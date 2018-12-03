<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logging;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Illuminate\Support\Collection;
use Ratchet\ConnectionInterface;

class Logger
{
    /** @var Statistic[] */
    protected $statistics = [];

    /** @var ChannelManager */
    protected $channelManager;

    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    public function webSocketMessage(ConnectionInterface $connection)
    {
        $this->initializeStatistics($connection->app->id);

        $this->statistics[$connection->app->id]->webSocketMessage();
    }

    public function apiMessage(ConnectionInterface $connection)
    {
        $this->initializeStatistics($connection->app->id);

        $this->statistics[$connection->app->id]->apiMessage();
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
            // TODO: perform http request

            // Reset connection and message count
            $connections = Collection::make($this->channelManager->getChannels($appId))->sum(function ($channel) {
                return count($channel->getSubscribedConnections());
            });

            $statistic->reset($connections);
        }
    }
}