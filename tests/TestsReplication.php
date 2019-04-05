<?php

namespace BeyondCode\LaravelWebSockets\Tests;

use React\EventLoop\Factory;
use BeyondCode\LaravelWebSockets\PubSub\Fake\FakeReplication;
use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;

trait TestsReplication
{
    public function setupReplication()
    {
        app()->singleton(ReplicationInterface::class, function () {
            return (new FakeReplication())->boot(Factory::create());
        });

        config([
            'websockets.replication.enabled' => true,
            'websockets.replication.driver' => 'fake',
        ]);
    }
}
