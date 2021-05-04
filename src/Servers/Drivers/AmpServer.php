<?php

namespace BeyondCode\LaravelWebSockets\Servers\Drivers;

use Amp\Http\Server\HttpServer;
use Amp\Socket\Server as AmpSocket;
use Amp\Websocket\Server\Websocket;
use BeyondCode\LaravelWebSockets\Contracts\Loop;
use BeyondCode\LaravelWebSockets\Contracts\Server;
use BeyondCode\LaravelWebSockets\Routing\Router;
use BeyondCode\LaravelWebSockets\WebSocketHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AmpServer implements Server
{
    /**
     * The underlying Amp Websocket Server.
     *
     * @var \Amp\Websocket\Server\Websocket
     */
    public $server;

    /**
     * Current loop implementation.
     *
     * @var \BeyondCode\LaravelWebSockets\Contracts\Loop
     */
    protected $loop;

    /**
     * The default addresses to listen for connections.
     *
     * @var array|\Amp\Socket\Server[]
     */
    protected $addresses = [];

    /**
     * The console to output.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * The Router to handle incoming messages.
     *
     * @var \BeyondCode\LaravelWebSockets\Routing\Router
     */
    protected $router;

    /**
     * Logger to output the server state.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Http Server
     *
     * @var \Amp\Http\Server\HttpServer
     */
    protected $http;

    /**
     * AmpServer constructor.
     *
     * @param  \Amp\Websocket\Server\Websocket  $server
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Loop  $loop
     */
    public function __construct(Websocket $server, Loop $loop)
    {
        $this->server = $server;
        $this->loop = $loop;
    }

    /**
     * Sets the addresses to listen on.
     *
     * @param  string  ...$address
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Server
     */
    public function listenOn(string ...$address): Server
    {
        foreach ($address as $socket) {
            $this->addresses[$socket] = AmpSocket::listen($socket);
        }

        return $this;
    }

    /**
     * Sets the output interface to write server information.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Server
     */
    public function outputTo(OutputInterface $output): Server
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Sets a logger to output the server state.
     *
     * @param  \Psr\Log\LoggerInterface  $logger
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Server
     */
    public function logTo(LoggerInterface $logger): Server
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Sets the router to handle the incoming messages.
     *
     * @param  \BeyondCode\LaravelWebSockets\Routing\Router  $router
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Server
     */
    public function routeUsing(Router $router): Server
    {
        $this->router = $router;

        return $this;
    }

    /**
     * Starts the server. Can accept a callable to handle the requests.
     *
     * Execution of the Server is blocking, and only itself can be stopped.
     *
     * @param  callable|null  $callable
     *
     * @return void
     */
    public function start(callable $callable = null): void
    {
        $this->http = new HttpServer($this->addresses, $this->server, $this->logger);

        $this->http->start();
    }

    /**
     * Stops the server. Can accept a callable to handle the stopping procedures.
     *
     * @param  callable|null  $callable
     *
     * @return void
     */
    public function stop(callable $callable = null): void
    {
        $this->http->stop();
    }
}
