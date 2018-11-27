<?php

namespace BeyondCode\LaravelWebSockets\Server;

use BeyondCode\LaravelWebSockets\Server\Logger\WebsocketLogger;
use BeyondCode\LaravelWebSockets\HttpApi\Controllers\FetchChannelController;
use BeyondCode\LaravelWebSockets\HttpApi\Controllers\FetchChannelsController;
use BeyondCode\LaravelWebSockets\HttpApi\Controllers\FetchUsersController;
use BeyondCode\LaravelWebSockets\HttpApi\Controllers\TriggerEventController;
use BeyondCode\LaravelWebSockets\WebSockets\Controllers\WebSocketHandler;
use Ratchet\WebSocket\MessageComponentInterface;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use BeyondCode\LaravelWebSockets\Exceptions\InvalidWebSocketController;

class Router
{
    /** @var \Symfony\Component\Routing\RouteCollection */
    protected $routes;

    public function __construct()
    {
        $this->routes = new RouteCollection;
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    public function echo()
    {
        $this->get('/app/{appKey}', WebSocketHandler::class);

        $this->get('/apps/{appId}/channels', FetchChannelsController::class);
        $this->get('/apps/{appId}/channels/{channelName}', FetchChannelController::class);
        $this->get('/apps/{appId}/channels/{channelName}/users', FetchUsersController::class);
        $this->post('/apps/{appId}/events', TriggerEventController::class);
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

    public function websocket(string $uri, $action)
    {
        if (!is_subclass_of($action, MessageComponentInterface::class)) {
            throw InvalidWebSocketController::withController($action);
        }

        $this->get($uri, $action);
    }

    public function addRoute(string $method, string $uri, $action)
    {
        $this->routes->add($uri, $this->getRoute($method, $uri, $action));
    }

    protected function getRoute(string $method, string $uri, $action): Route
    {
        /**
         * If the given action is a class that handles WebSockets, then it's not a regular
         * controller but a WebSocketHandler that needs to converted to a WsServer.
         *
         * If the given action is a regular controller we'll just instanciate it.
         */
        $action = is_subclass_of($action, MessageComponentInterface::class)
            ? $this->createWebSocketsServer($action)
            : app($action);

        return new Route($uri, ['_controller' => $action], [], [], null, [], [$method]);
    }

    protected function createWebSocketsServer(MessageComponentInterface $action): WsServer
    {
        $app = app($action);

        if (WebsocketLogger::isEnabled()) {
            $app = WebsocketLogger::decorate($app);
        }

        return new WsServer($app);
    }
}