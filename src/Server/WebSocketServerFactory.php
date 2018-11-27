<?php

namespace BeyondCode\LaravelWebSockets\Server;

use BeyondCode\LaravelWebSockets\Server\Logger\HttpLogger;
use Ratchet\Http\Router;
use React\Socket\SecureServer;
use React\Socket\Server;
use Ratchet\Server\IoServer;
use React\EventLoop\LoopInterface;
use React\EventLoop\Factory as LoopFactory;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RouteCollection;

class WebSocketServerFactory
{
    /** @var string */
    protected $host = '127.0.0.1';

    /** @var int */
    protected $port = 8080;

    /** @var \React\EventLoop\LoopInterface */
    protected $loop;

    /** @var \Symfony\Component\Routing\RouteCollection */
    protected $routes;

    /** @var Symfony\Component\Console\Output\OutputInterface */
    protected $consoleOutput;

    public function __construct(RouteCollection $routes)
    {
        $this->loop = LoopFactory::create();

        $this->routes = $routes;
    }

    public function setHost(string $host)
    {
        $this->host = $host;

        return $this;
    }

    public function setPort(string $port)
    {
        $this->port = $port;

        return $this;
    }

    public function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;

        return $this;
    }

    public function setConsoleOutput(OutputInterface $consoleOutput)
    {
        $this->consoleOutput = $consoleOutput;

        return $this;
    }

    public function run()
    {
        $server = $this->createServer();

        $server->run();
    }

    protected function createServer(): IoServer
    {
        $socket = new Server("{$this->host}:{$this->port}", $this->loop);

        if (config('websockets.ssl.local_cert')) {
            $socket = new SecureServer($socket, $this->loop, config('websockets.ssl'));
        }

        $urlMatcher = new UrlMatcher($this->routes, new RequestContext);

        $router = new Router($urlMatcher);

        $app = new OriginCheck($router, config('websockets.allowedOrigins', []));

        $httpServer = new HttpServer($app, config('websockets.maxRequestSizeInKb') * 1024);

        if (HttpLogger::isEnabled()) {
            $httpServer = HttpLogger::decorate($httpServer);
        }

        return new IoServer($httpServer, $socket, $this->loop);
    }
}
