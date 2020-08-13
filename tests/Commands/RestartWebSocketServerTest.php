<?php

namespace BeyondCode\LaravelWebSockets\Tests\Commands;

use Artisan;
use BeyondCode\LaravelWebSockets\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\InteractsWithTime;

class RestartWebSocketServerTest extends TestCase
{
    use InteractsWithTime;

    /** @test */
    public function it_can_broadcast_restart_signal()
    {
        $start = $this->currentTime();

        Artisan::call('websockets:restart');

        $this->assertGreaterThanOrEqual($start, Cache::get('beyondcode:websockets:restart', 0));
    }
}
