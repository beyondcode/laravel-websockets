<?php

namespace BeyondCode\LaravelWebSockets;

use Ratchet\WebSocket\WsServer;
use Symfony\Component\Routing\Route;
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

    public function addRoute($uri, $action)
    {
        if (!is_subclass_of($action, WebSocketController::class)) {
            throw InvalidWebSocketController::withController($action);
        }

        $this->routes->add($uri, $this->getRoute($uri, $action));
    }

    protected function getRoute($uri, $action): Route
    {
        return new Route($uri, ['_controller' => $this->wrapController($action)], [], [], null, [], ['GET']);
    }

    /**
     * Wrap WebSocket controllers with Ratchets WsServer.
     */
    protected function wrapController($controller)
    {
        if (is_subclass_of($controller, WebSocketController::class)) {
            return new WsServer(app($controller));
        }

        return app($controller);
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }
}