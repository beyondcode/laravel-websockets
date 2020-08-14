<?php

namespace BeyondCode\LaravelWebSockets\Tests\HttpApi;

use BeyondCode\LaravelWebSockets\Tests\TestCase;

class FetchChannelsReplicationTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->runOnlyOnRedisReplication();
    }
}
