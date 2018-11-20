<?php

namespace BeyondCode\LaravelWebsockets\Server;

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

/**
 * An opinionated facade class to quickly and easily create a WebSocket server.
 * A few configuration assumptions are made and some best-practice security conventions are applied by default.
 */
class WebSocketServer {
    /**
     * @var \Ratchet\Server\IoServer
     */
    public $flashServer;

    /**
     * @var \Ratchet\Server\IoServer
     */
    protected $_server;

    /**
     * The Host passed in construct used for same origin policy
     * @var string
     */
    protected $httpHost;

    /***
     * The port the socket is listening
     * @var int
     */
    protected $port;

    /**
     * @var int
     */
    protected $_routeCounter = 0;

    /**
     * @param string        $httpHost   HTTP hostname clients intend to connect to. MUST match JS `new WebSocket('ws://$httpHost');`
     * @param int           $port       Port to listen on. If 80, assuming production, Flash on 843 otherwise expecting Flash to be proxied through 8843
     * @param string        $address    IP address to bind to. Default is localhost/proxy only. '0.0.0.0' for any machine.
     * @param LoopInterface $loop       Specific React\EventLoop to bind the application to. null will create one for you.
     */
    public function __construct($httpHost = 'localhost', $port = 8080, $address = '127.0.0.1', LoopInterface $loop = null) {
        if (extension_loaded('xdebug')) {
            trigger_error('XDebug extension detected. Remember to disable this if performance testing or going live!', E_USER_WARNING);
        }

        if (null === $loop) {
            $loop = LoopFactory::create();
        }

        $this->httpHost = $httpHost;
        $this->port = $port;

        $socket = new Reactor($address . ':' . $port, $loop);

        $this->_server = new IoServer(new HttpServer(new Router(new UrlMatcher(WebSocketRouter::getRoutes(), new RequestContext))), $socket, $loop);

        $policy = new FlashPolicy;
        $policy->addAllowedAccess($httpHost, 80);
        $policy->addAllowedAccess($httpHost, $port);

        if (80 == $port) {
            $flashUri = '0.0.0.0:843';
        } else {
            $flashUri = 8843;
        }
        $flashSock = new Reactor($flashUri, $loop);
        $this->flashServer = new IoServer($policy, $flashSock);
    }

    /**
     * Run the server by entering the event loop
     */
    public function run() {
        $this->_server->run();
    }
}
