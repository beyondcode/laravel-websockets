<?php

namespace BeyondCode\LaravelWebSockets\Test\Commands;

use BeyondCode\LaravelWebSockets\Test\TestCase;

class StartServerTest extends TestCase
{
    public function test_does_not_fail_if_building_up()
    {
        $this->loop->futureTick(function () {
            $this->loop->stop();
        });

        $this->artisan('websockets:serve', ['--loop' => $this->loop, '--debug' => true, '--port' => 6001]);

        $this->assertTrue(true);
    }

    public function test_pcntl_sigint_signal()
    {
        $this->loop->futureTick(function () {
            $this->newActiveConnection(['public-channel']);
            $this->newActiveConnection(['public-channel']);

            posix_kill(posix_getpid(), SIGINT);

            $this->loop->stop();
        });

        $this->artisan('websockets:serve', ['--loop' => $this->loop, '--debug' => true, '--port' => 6002]);

        $this->assertTrue(true);
    }

    public function test_pcntl_sigterm_signal()
    {
        $this->loop->futureTick(function () {
            $this->newActiveConnection(['public-channel']);
            $this->newActiveConnection(['public-channel']);

            posix_kill(posix_getpid(), SIGTERM);

            $this->loop->stop();
        });

        $this->artisan('websockets:serve', ['--loop' => $this->loop, '--debug' => true, '--port' => 6003]);

        $this->assertTrue(true);
    }
}
