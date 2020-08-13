<?php

namespace BeyondCode\LaravelWebSockets\Tests\HttpApi;

use BeyondCode\LaravelWebSockets\Tests\TestsReplication;

class FetchChannelReplicationTest extends FetchChannelTest
{
    use TestsReplication;

    public function setUp(): void
    {
        parent::setUp();

        $this->setupReplication();
    }
}
