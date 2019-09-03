<?php

namespace BeyondCode\LaravelWebSockets\Tests;

use Illuminate\Support\Facades\Config;
use BeyondCode\LaravelWebSockets\PubSub\Drivers\LocalClient;
use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;

trait TestsReplication
{
    public function setupReplication()
    {
        app()->singleton(ReplicationInterface::class, function () {
            return new LocalClient();
        });

        Config::set([
            'websockets.replication.enabled' => true,
            'websockets.replication.driver' => 'redis',
        ]);
    }
}
