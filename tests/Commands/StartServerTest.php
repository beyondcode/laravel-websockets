<?php

namespace BeyondCode\LaravelWebSockets\Test\Commands;

use BeyondCode\LaravelWebSockets\Test\TestCase;

class StartServerTest extends TestCase
{
    public function test_does_not_fail_if_building_up()
    {
        $this->artisan('websockets:serve', ['--test' => true, '--debug' => true]);

        $this->assertTrue(true);
    }
}
