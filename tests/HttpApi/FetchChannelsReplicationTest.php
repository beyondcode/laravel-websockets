<?php

namespace BeyondCode\LaravelWebSockets\Tests\HttpApi;

use BeyondCode\LaravelWebSockets\Tests\TestsReplication;

class FetchChannelsReplicationTest extends FetchChannelsTest
{
    use TestsReplication;

    public function setUp(): void
    {
        parent::setUp();

        $this->setupReplication();
    }
}
