<?php

namespace BeyondCode\LaravelWebSockets\Tests\Commands;

use Artisan;
use BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry;
use BeyondCode\LaravelWebSockets\Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StartWebSocketServerTest extends TestCase
{
    /** @test */
    public function does_not_fail_if_building_up()
    {
        $this->artisan('websockets:serve', ['--test' => true]);

        $this->assertTrue(true);
    }
}
