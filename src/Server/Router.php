<?php

namespace BeyondCode\LaravelWebSockets\Server;

use BeyondCode\LaravelWebSockets\Exceptions\InvalidWebSocketController;
use BeyondCode\LaravelWebSockets\HttpApi\Controllers\FetchChannelController;
use BeyondCode\LaravelWebSockets\HttpApi\Controllers\FetchChannelsController;
use BeyondCode\LaravelWebSockets\HttpApi\Controllers\FetchUsersController;
use BeyondCode\LaravelWebSockets\HttpApi\Controllers\TriggerEventController;
use BeyondCode\LaravelWebSockets\Server\Logger\WebsocketsLogger;
use BeyondCode\LaravelWebSockets\WebSockets\WebSocketHandler;
use Illuminate\Support\Collection;
use Ratchet\WebSocket\MessageComponentInterface;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Router
{
    /** @var \Symfony\Component\Routing\RouteCollection */
    protected $routes;
    protected $customRoutes;

    public function __construct()
    {
        $this->routes = new RouteCollection;
        $this->customRoutes = new Collection();
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    public function echo()
    {
        $this->get('/app/{appKey}', WebSocketHandler::class);

        $this->post('/apps/{appId}/events', TriggerEventController::class);
        $this->get('/apps/{appId}/channels', FetchChannelsController::class);
        $this->get('/apps/{appId}/channels/{channelName}', FetchChannelController::class);
        $this->get('/apps/{appId}/channels/{channelName}/users', FetchUsersController::class);
    }

    public function customRoutes()
    {
        $this->customRoutes->each(function ($action, $uri) {
            $this->get($uri, $action);
        });
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

    public function webSocket(string $uri, $action)
    {
        if (! is_subclass_of($action, MessageComponentInterface::class)) {
            throw InvalidWebSocketController::withController($action);
        }

        $this->customRoutes->put($uri, $action);
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

    protected function createWebSocketsServer(string $action): WsServer
    {
        $app = app($action);

        if (WebsocketsLogger::isEnabled()) {
            $app = WebsocketsLogger::decorate($app);
        }

        return new WsServer($app);
    }
}
