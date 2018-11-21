<?php

namespace BeyondCode\LaravelWebSockets\Server;

use Ratchet\Http\Router;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\Server\FlashPolicy;
use React\EventLoop\LoopInterface;
use React\Socket\Server as Reactor;
use React\EventLoop\Factory as LoopFactory;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use BeyondCode\LaravelWebSockets\Facades\WebSocketRouter;

class WebSocketServer
{
    /** @var \Ratchet\Server\IoServer */
    protected $server;

    public function __construct($port = 8080, $address = '127.0.0.1', LoopInterface $loop)
    {
        $socket = new Reactor($address . ':' . $port, $loop);

        $this->server = new IoServer(new HttpServer(new Router(new UrlMatcher(WebSocketRouter::getRoutes(), new RequestContext))), $socket, $loop);
    }

    public function run()
    {
        $this->server->run();
    }
}
