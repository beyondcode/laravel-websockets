<?php

namespace BeyondCode\LaravelWebSockets\Loop;

use BeyondCode\LaravelWebSockets\Contracts\Loop;
use Illuminate\Support\Manager;

/**
 * @method \BeyondCode\LaravelWebSockets\Contracts\Promise run(callable $callable)
 * @method void start()
 * @method \BeyondCode\LaravelWebSockets\Contracts\Promise delay(int $milliseconds, callable $callable)
 * @method void repeat(int $milliseconds, callable $callable)
 * @method void stop()
 *
 * @method \BeyondCode\LaravelWebSockets\Contracts\Loop driver($driver = null)
 */
class EventLoopManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return 'amp';
    }

    /**
     * Creates the Amp Loop Driver.
     *
     * @return \BeyondCode\LaravelWebSockets\Loop\Drivers\Amp
     */
    protected function createAmpDriver(): Loop
    {
        return new Drivers\Amp();
    }
}
