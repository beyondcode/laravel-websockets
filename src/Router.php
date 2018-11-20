<?php

namespace BeyondCode\LaravelWebSockets;

use Ratchet\WebSocket\WsServer;
use Symfony\Component\Routing\Route;
use Ratchet\Http\HttpServerInterface;
use Symfony\Component\Routing\RouteCollection;
use BeyondCode\LaravelWebSockets\Exceptions\InvalidWebSocketController;

class Router
{
    /** @var RouteCollection */
    protected $routes;

    public function __construct()
    {
        $this->routes = new RouteCollection;
    }

    /**
     * Add a new WebSocket route.
     *
     * @param $uri
     * @param $action
     */
    public function websocket($uri, $action)
    {
        if (!is_subclass_of($action, WebSocketController::class)) {
            throw InvalidWebSocketController::withController($action);
        }

        $this->addRoute($uri, $action);
    }

    public function addRoute($uri, $action)
    {
        $this->routes->add($uri, $this->getRoute($uri, $action));
    }

    protected function getRoute($uri, $action): Route
    {
        return new Route($uri, ['_controller' => $this->wrapAction($action)], [], [], null, [], ['GET']);
    }

    /**
     * Register the required Laravel Echo routes
     */
    public function echo()
    {
        //$this->addRoute('/', EchoWebsocketServer::class);
        $this->addRoute('/apps/{appId}/status', LaravelEcho\Http\Controllers\StatusController::class);
        //$this->addRoute('/apps/{appId}/channels', 'ChannelController@index');
    }

    /**
     * Wrap WebSocket controllers with Ratchets WsServer.
     * If the action is not a WebSocketController, wrap it with our HttpServerInstance
     *
     * @param $action
     * @return WsServer|HttpServerInterface
     */
    protected function wrapAction($action)
    {
        if (is_subclass_of($action, WebSocketController::class)) {
            return new WsServer(app($action));
        }

        return app($action);
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }
}