<?php

namespace BeyondCode\LaravelWebSockets\Contracts;

use BeyondCode\LaravelWebSockets\Routing\Router;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface Server
{
    /**
     * Sets the addresses to listen on.
     *
     * @param  string  ...$address
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Server
     */
    public function listenOn(string ...$address): self;

    /**
     * Sets the output interface to write server information.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Server
     */
    public function outputTo(OutputInterface $output): self;

    /**
     * Sets a logger to output the server state.
     *
     * @param  \Psr\Log\LoggerInterface  $logger
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Server
     */
    public function logTo(LoggerInterface $logger): self;

    /**
     * Sets the router to handle the incoming messages.
     *
     * @param  \BeyondCode\LaravelWebSockets\Routing\Router  $router
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Server
     */
    public function routeUsing(Router $router): self;

    /**
     * Starts the server. Can accept a callable to handle the requests.
     *
     * Execution of the Server is blocking, and only itself can be stopped.
     *
     * @param  callable|null  $callable
     *
     * @return void
     */
    public function start(callable $callable = null): void;

    /**
     * Stops the server. Can accept a callable to handle the stopping procedures.
     *
     * @param  callable|null  $callable
     *
     * @return void
     */
    public function stop(callable $callable = null): void;
}
