<?php

namespace BeyondCode\LaravelWebSockets;

use Amp\Http\Server\HttpServer;
use Amp\Socket\Server;
use Amp\Websocket\Server\Websocket;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouteCollection;

class ServerFactory
{
    /**
     * The routes to register.
     *
     * @var \Illuminate\Routing\RouteCollection
     */
    protected $routes;

    /**
     * Console output.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $consoleOutput;

    /**
     * Sockets to open and listen on.
     *
     * @var array|string[]
     */
    protected $listen = [];

    /**
     * Logger to use with Websockets.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Initialize the class.
     *
     * @param  \Psr\Log\LoggerInterface  $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Sets the sockets addresses to listen on.
     *
     * @param  array  $listen
     *
     * @return \BeyondCode\LaravelWebSockets\ServerFactory
     */
    public function listenOn(array $listen): ServerFactory
    {
        $this->listen = $listen;

        return $this;
    }

    /**
     * Add the routes.
     *
     * @param  \Symfony\Component\Routing\RouteCollection  $routes
     *
     * @return $this
     */
    public function withRoutes(RouteCollection $routes): ServerFactory
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * Set the console output.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $consoleOutput
     * @return $this
     */
    public function setConsoleOutput(OutputInterface $consoleOutput): ServerFactory
    {
        $this->consoleOutput = $consoleOutput;

        return $this;
    }

    /**
     * Set up the server.
     *
     * @return \Amp\Http\Server\HttpServer
     * @throws \Amp\Socket\SocketException
     */
    public function createServer(): HttpServer
    {
        $sockets = [];

        foreach ($this->listen as $socket) {
            $sockets[] = Server::listen($socket);
        }

        return new HttpServer($sockets, new Websocket(new WebSocketHandler()), $this->logger);
    }
}
