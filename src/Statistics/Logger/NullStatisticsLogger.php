<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logger;

use BeyondCode\LaravelWebSockets\Statistics\Drivers\StatisticsDriver;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;

class NullStatisticsLogger implements StatisticsLogger
{
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
     * @param  mixed  $appId
     * @return void
     */
    public function connection($appId)
    {
        //
    }

    /**
     * Handle disconnections.
     *
     * @param  mixed  $appId
     * @return void
     */
    public function disconnection($appId)
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
