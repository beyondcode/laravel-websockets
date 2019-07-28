<?php

namespace BeyondCode\LaravelWebSockets\Tests;

use React\EventLoop\Factory;
use Illuminate\Support\Facades\Config;
use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;
use BeyondCode\LaravelWebSockets\Tests\Mocks\FakeReplicationClient;

trait TestsReplication
{
    public function setupReplication()
    {
        app()->singleton(ReplicationInterface::class, function () {
            return (new FakeReplicationClient())->boot(Factory::create());
        });

        Config::set([
            'websockets.replication.enabled' => true,
            'websockets.replication.driver' => 'redis',
        ]);
    }
}
