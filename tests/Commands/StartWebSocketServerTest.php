<?php

namespace BeyondCode\LaravelWebSockets\Tests\Commands;

use BeyondCode\LaravelWebSockets\Tests\TestCase;

class StartWebSocketServerTest extends TestCase
{
    /** @test */
    public function does_not_fail_if_building_up()
    {
        $this->artisan('websockets:serve', ['--test' => true]);

        $this->assertTrue(true);
    }
}
