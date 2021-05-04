<?php

namespace BeyondCode\LaravelWebSockets\Contracts;

interface Loop
{
    /**
     * Starts the Loop and wait until it finishes.
     *
     * @return void
     */
    public function start(): void;

    /**
     * Stop the loop and all callables.
     *
     * @return void
     */
    public function stop(): void;

    /**
     * Creates a new Promise, but doesn't run it.
     *
     * @param  callable  $callable
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise
     */
    public function new(callable $callable): Promise;

    /**
     * Runs a callable immediately and wait until it finishes.
     *
     * @param  callable|null  $callable
     *
     * @return mixed
     */
    public function run(callable $callable);

    /**
     * Creates a Promise set to be executed in the future.
     *
     * @param  int  $milliseconds
     * @param  callable  $callable
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise
     */
    public function delay(int $milliseconds, callable $callable): Promise;

    /**
     * Creates a Promise that will execute a each milliseconds passed.
     *
     * @param  int  $milliseconds
     * @param  callable  $callable
     *
     * @return void
     */
    public function repeat(int $milliseconds, callable $callable): void;

    /**
     * Creates a Promise that will execute when a given received signal is received.
     *
     * @param  string  $signal
     * @param  callable  $callable
     */
    public function onSignal(string $signal, callable $callable): void;
}
