<?php

namespace BeyondCode\LaravelWebSockets\Server;

use Ratchet\Http\Router;
use React\Socket\Server;
use Ratchet\Server\IoServer;
use React\Socket\SecureServer;
use React\EventLoop\LoopInterface;
use Illuminate\Contracts\Config\Repository;
use React\EventLoop\Factory as LoopFactory;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Console\Output\OutputInterface;
use BeyondCode\LaravelWebSockets\Server\Logger\HttpLogger;

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

    /** @var \Symfony\Component\Console\Output\OutputInterface */
    protected $consoleOutput;

    /** @var \Illuminate\Contracts\Config\Repository */
    protected $config;

    public function __construct(Repository $config)
    {
        $this->loop = LoopFactory::create();
        $this->config = $config;
    }

    public function useRoutes(RouteCollection $routes)
    {
        $this->routes = $routes;

        return $this;
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

    public function createServer(): IoServer
    {
        $socket = new Server("{$this->host}:{$this->port}", $this->loop);

        if ($this->config->get('websockets.ssl.local_cert')) {
            $socket = new SecureServer($socket, $this->loop, $this->config->get('websockets.ssl'));
        }

        $urlMatcher = new UrlMatcher($this->routes, new RequestContext);

        $router = new Router($urlMatcher);

        $app = new OriginCheck($router, $this->config->get('websockets.allowed_origins', []));

        $httpServer = new HttpServer($app, $this->config->get('websockets.max_request_size_in_kb') * 1024);

        if (HttpLogger::isEnabled()) {
            $httpServer = HttpLogger::decorate($httpServer);
        }

        return new IoServer($httpServer, $socket, $this->loop);
    }
}
