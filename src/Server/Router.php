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
    /**
     * The implemented routes.
     *
     * @var \Symfony\Component\Routing\RouteCollection
     */
    protected $routes;

    /**
     * The custom routes defined by the user.
     *
     * @var \Symfony\Component\Routing\RouteCollection
     */
    protected $customRoutes;

    /**
     * Initialize the class.
     *
     * @return void
     */
    public function __construct()
    {
        $this->routes = new RouteCollection;
        $this->customRoutes = new Collection();
    }

    /**
     * Get the routes.
     *
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * Register the routes.
     *
     * @return void
     */
    public function routes()
    {
        $this->get('/app/{appKey}', config('websockets.handlers.websocket', WebSocketHandler::class));

        $this->post('/apps/{appId}/events', TriggerEventController::class);
        $this->get('/apps/{appId}/channels', FetchChannelsController::class);
        $this->get('/apps/{appId}/channels/{channelName}', FetchChannelController::class);
        $this->get('/apps/{appId}/channels/{channelName}/users', FetchUsersController::class);

        $this->customRoutes->each(function ($action, $uri) {
            $this->get($uri, $action);
        });
    }

    /**
     * Add a GET route.
     *
     * @param  string  $uri
     * @param  string  $action
     * @return void
     */
    public function get(string $uri, $action)
    {
        $this->addRoute('GET', $uri, $action);
    }

    /**
     * Add a POST route.
     *
     * @param  string  $uri
     * @param  string  $action
     * @return void
     */
    public function post(string $uri, $action)
    {
        $this->addRoute('POST', $uri, $action);
    }

    /**
     * Add a PUT route.
     *
     * @param  string  $uri
     * @param  string  $action
     * @return void
     */
    public function put(string $uri, $action)
    {
        $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Add a PATCH route.
     *
     * @param  string  $uri
     * @param  string  $action
     * @return void
     */
    public function patch(string $uri, $action)
    {
        $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Add a DELETE route.
     *
     * @param  string  $uri
     * @param  string  $action
     * @return void
     */
    public function delete(string $uri, $action)
    {
        $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Add a WebSocket GET route that should
     * comply with the MessageComponentInterface interface.
     *
     * @param  string  $uri
     * @param  string  $action
     * @return void
     */
    public function webSocket(string $uri, $action)
    {
        if (! is_subclass_of($action, MessageComponentInterface::class)) {
            throw InvalidWebSocketController::withController($action);
        }

        $this->customRoutes->put($uri, $action);
    }

    /**
     * Add a new route to the list.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  string  $action
     * @return void
     */
    public function addRoute(string $method, string $uri, $action)
    {
        $this->routes->add($uri, $this->getRoute($method, $uri, $action));
    }

    /**
     * Get the route of a specified method, uri and action.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  string  $action
     * @return \Symfony\Component\Routing\Route
     */
    protected function getRoute(string $method, string $uri, $action): Route
    {
        /**
         * If the given action is a class that handles WebSockets, then it's not a regular
         * controller but a WebSocketHandler that needs to converted to a WsServer.
         *
         * If the given action is a regular controller we'll just instantiate it.
         */
        $action = is_subclass_of($action, MessageComponentInterface::class)
            ? $this->createWebSocketsServer($action)
            : app($action);

        return new Route($uri, ['_controller' => $action], [], [], null, [], [$method]);
    }

    /**
     * Create a new websockets server to handle the action.
     *
     * @param  string  $action
     * @return \Ratchet\WebSocket\WsServer
     */
    protected function createWebSocketsServer(string $action): WsServer
    {
        $app = app($action);

        if (WebsocketsLogger::isEnabled()) {
            $app = WebsocketsLogger::decorate($app);
        }

        return new WsServer($app);
    }
}
