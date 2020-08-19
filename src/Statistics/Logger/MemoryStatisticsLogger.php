<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logger;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Statistics\Http\Controllers\WebSocketStatisticsEntriesController;
use BeyondCode\LaravelWebSockets\Statistics\Statistic;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Clue\React\Buzz\Browser;
use function GuzzleHttp\Psr7\stream_for;
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
     * The Browser instance.
     *
     * @var \Clue\React\Buzz\Browser
     */
    protected $browser;

    /**
     * Initialize the logger.
     *
     * @param  \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager  $channelManager
     * @param  \Clue\React\Buzz\Browser  $browser
     * @return void
     */
    public function __construct(ChannelManager $channelManager, Browser $browser)
    {
        $this->channelManager = $channelManager;
        $this->browser = $browser;
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

            $postData = array_merge($statistic->toArray(), [
                'secret' => App::findById($appId)->secret,
            ]);

            $this->browser
                ->post(
                    action([WebSocketStatisticsEntriesController::class, 'store']),
                    ['Content-Type' => 'application/json'],
                    stream_for(json_encode($postData))
                );

            $currentConnectionCount = $this->channelManager->getConnectionCount($appId);

            $statistic->reset($currentConnectionCount);
        }

        $this->statistics = [];
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
