<?php

namespace BeyondCode\LaravelWebSockets\Loop\Drivers;

use Amp\Delayed;
use Amp\LazyPromise;
use Amp\Loop as AmpLoop;
use BeyondCode\LaravelWebSockets\Contracts\Loop;
use BeyondCode\LaravelWebSockets\Contracts\Promise;

class Amp implements Loop
{
    /**
     * Starts the Loop and wait until it finishes.
     *
     * @return void
     */
    public function start(): void
    {
        AmpLoop::run();
    }

    /**
     * Stop the loop and all callables.
     *
     * @return void
     */
    public function stop(): void
    {
        AmpLoop::stop();
    }

    /**
     * Creates a new Promise, but doesn't run it.
     *
     * @param  callable  $callable
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise
     */
    public function new(callable $callable): Promise
    {
        return new AmpPromise(new LazyPromise($callable));
    }

    /**
     * Runs a callable and wait until it finishes.
     *
     * @param  callable|null  $callable
     *
     * @return mixed
     */
    public function run(callable $callable)
    {
        return $this->new($callable)->return();
    }

    /**
     * Creates a pending Promise that will be executed in the future.
     *
     * @param  int  $milliseconds
     * @param  callable  $callable
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise
     */
    public function delay(int $milliseconds, callable $callable): Promise
    {
        return new AmpPromise(new Delayed($milliseconds, new LazyPromise($callable)));
    }

    /**
     * Creates a Promise that will execute a each milliseconds passed.
     *
     * @param  int  $milliseconds
     * @param  callable  $callable
     *
     * @return void
     */
    public function repeat(int $milliseconds, callable $callable): void
    {
        AmpLoop::repeat($milliseconds, $callable);
    }

    /**
     * Creates a Promise that will execute when a given received signal is received.
     *
     * @param  string  $signal
     * @param  callable  $callable
     */
    public function onSignal(string $signal, callable $callable): void
    {
        AmpLoop::onSignal($signal, $callable);
    }
}
