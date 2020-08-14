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

    public function test_not_implemented()
    {
        $this->markTestIncomplete(
            'Not yet implemented tests.'
        );
    }
}
