<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Logger;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use Clue\React\Buzz\Browser;
use Ratchet\ConnectionInterface;

class NullStatisticsLogger implements StatisticsLogger
{
    /** @var \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager */
    protected $channelManager;

    /** @var \Clue\React\Buzz\Browser */
    protected $browser;

    public function __construct(ChannelManager $channelManager, Browser $browser)
    {
        $this->channelManager = $channelManager;
        $this->browser = $browser;
    }

    public function webSocketMessage(ConnectionInterface $connection)
    {
        //
    }

    public function apiMessage($appId)
    {
        //
    }

    public function connection(ConnectionInterface $connection)
    {
        //
    }

    public function disconnection(ConnectionInterface $connection)
    {
        //
    }

    public function save()
    {
        //
    }
}
