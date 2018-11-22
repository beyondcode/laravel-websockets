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

    public function websocket(string $uri, $action)
    {
        if (!is_subclass_of($action, WebSocketController::class)) {
            throw InvalidWebSocketController::withController($action);
        }

        $this->get($uri, $action);
    }

    public function get(string $uri, $action)
    {
        $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, $action)
    {
        $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, $action)
    {
        $this->addRoute('PUT', $uri, $action);
    }

    public function patch(string $uri, $action)
    {
        $this->addRoute('PATCH', $uri, $action);
    }

    public function delete(string $uri, $action)
    {
        $this->addRoute('DELETE', $uri, $action);
    }

    public function addRoute(string $method, string $uri, $action)
    {
        $this->routes->add($uri, $this->getRoute($method, $uri, $action));
    }

    protected function getRoute(string $method, string $uri, $action): Route
    {
        return new Route($uri, ['_controller' => $this->wrapAction($action)], [], [], null, [], [$method]);
    }

    public function echo()
    {
        //TODO: add origin checker middleware
        $this->get('/app/{appId}', LaravelEcho\WebSocket\PusherServer::class);

        // TODO: fleshen out http API
        $this->get('/apps/{appId}/status', LaravelEcho\Http\Controllers\StatusController::class);
        $this->get('/apps/{appId}/channels', LaravelEcho\Http\Controllers\StatusController::class);
        $this->get('/apps/{appId}/channels/{channelName}', LaravelEcho\Http\Controllers\StatusController::class);
        $this->get('/apps/{appId}/channels/{channelName}/users', LaravelEcho\Http\Controllers\StatusController::class);

        $this->post('/apps/{appId}/events', LaravelEcho\Http\Controllers\EventController::class);
    }

    /**
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