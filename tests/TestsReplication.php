<?php

namespace BeyondCode\LaravelWebSockets\Tests;

use BeyondCode\LaravelWebSockets\Tests\Mocks\FakeReplicationClient;
use Illuminate\Support\Facades\Config;
use React\EventLoop\Factory;
use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;

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
