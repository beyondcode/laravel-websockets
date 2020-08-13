<?php

namespace BeyondCode\LaravelWebSockets\Tests\Channels;

use BeyondCode\LaravelWebSockets\Tests\TestsReplication;

class ChannelReplicationTest extends ChannelTest
{
    use TestsReplication;

    public function setUp() : void
    {
        parent::setUp();

        $this->setupReplication();
    }
}
