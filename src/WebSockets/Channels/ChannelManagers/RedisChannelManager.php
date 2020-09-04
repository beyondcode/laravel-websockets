<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManagers;

use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;

class RedisChannelManager extends ArrayChannelManager
{
    /**
     * The replicator driver.
     *
     * @var \BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface
     */
    protected $replicator;

    /**
     * Initialize the channel manager.
     *
     * @return void
     */
    public function __construct()
    {
        $this->replicator = app(ReplicationInterface::class);
    }

    /**
     * Get the connections count across multiple servers.
     *
     * @param  mixed  $appId
     * @return int|\React\Promise\PromiseInterface
     */
    public function getGlobalConnectionsCount($appId)
    {
        return $this->replicator->getGlobalConnectionsCount($appId);
    }
}
