<?php

namespace BeyondCode\LaravelWebSockets\Tests\HttpApi;

use BeyondCode\LaravelWebSockets\Tests\TestsReplication;

class FetchUsersReplicationTest extends FetchUsersTest
{
    use TestsReplication;

    public function setUp() : void
    {
        parent::setUp();

        $this->setupReplication();
    }
}
