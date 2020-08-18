<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logger;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Clue\React\Buzz\Browser;
use Ratchet\ConnectionInterface;

class NullStatisticsLogger implements StatisticsLogger
{
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
        //
    }

    /**
     * Handle the incoming API message.
     *
     * @param  mixed  $appId
     * @return void
     */
    public function apiMessage($appId)
    {
        //
    }

    /**
     * Handle the new conection.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function connection(ConnectionInterface $connection)
    {
        //
    }

    /**
     * Handle disconnections.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function disconnection(ConnectionInterface $connection)
    {
        //
    }

    /**
     * Save all the stored statistics.
     *
     * @return void
     */
    public function save()
    {
        //
    }
}
