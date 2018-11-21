<?php

namespace BeyondCode\LaravelWebSockets;

use Ratchet\WebSocket\WsServer;
use Symfony\Component\Routing\Route;
use Ratchet\Http\HttpServerInterface;
use Symfony\Component\Routing\RouteCollection;
use Ratchet\WebSocket\MessageComponentInterface;
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

        $this->get($uri, $action);
    }

    public function get($uri, $action)
    {
        $this->addRoute('GET', $uri, $action);
    }

    public function post($uri, $action)
    {
        $this->addRoute('POST', $uri, $action);
    }

    public function put($uri, $action)
    {
        $this->addRoute('PUT', $uri, $action);
    }

    public function patch($uri, $action)
    {
        $this->addRoute('PATCH', $uri, $action);
    }

    public function delete($uri, $action)
    {
        $this->addRoute('DELETE', $uri, $action);
    }

    public function addRoute($method, $uri, $action)
    {
        $this->routes->add($uri, $this->getRoute($method, $uri, $action));
    }

    protected function getRoute($method, $uri, $action): Route
    {
        return new Route($uri, ['_controller' => $this->wrapAction($action)], [], [], null, [], [$method]);
    }

    /**
     * Register the required Laravel Echo routes
     */
    public function echo()
    {
        //TODO: add orgin checker middleware

        $this->get('/app/{appId}', LaravelEcho\WebSocket\EchoServer::class);

        // TODO: fleshen out http API
        $this->get('/apps/{appId}/status', LaravelEcho\Http\Controllers\StatusController::class);
        $this->get('/apps/{appId}/channels', LaravelEcho\Http\Controllers\StatusController::class);
        $this->get('/apps/{appId}/channels/{channelName}', LaravelEcho\Http\Controllers\StatusController::class);
        $this->get('/apps/{appId}/channels/{channelName}/users', LaravelEcho\Http\Controllers\StatusController::class);

        $this->post('/apps/{appId}/events', LaravelEcho\Http\Controllers\EventController::class);
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