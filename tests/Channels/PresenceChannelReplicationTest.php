<?php

namespace BeyondCode\LaravelWebSockets\Tests\Channels;

use BeyondCode\LaravelWebSockets\Tests\TestsReplication;

class PresenceChannelReplicationTest extends PresenceChannelTest
{
    use TestsReplication;

    public function setUp() : void
    {
        parent::setUp();

        $this->setupReplication();
    }
}
