<?php

namespace BeyondCode\LaravelWebSockets\Server;

use BeyondCode\LaravelWebSockets\Server\Loggers\WebSocketsLogger;
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
     * Define the custom routes.
     *
     * @var array
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

        $this->customRoutes = [
            'get' => new Collection,
            'post' => new Collection,
            'put' => new Collection,
            'patch' => new Collection,
            'delete' => new Collection,
        ];
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
     * Get the list of routes that still need to be registered.
     *
     * @return array[Collection]
     */
    public function getCustomRoutes(): array
    {
        return $this->customRoutes;
    }

    /**
     * Register the default routes.
     *
     * @return void
     */
    public function registerRoutes()
    {
        $this->get('/app/{appKey}', config('websockets.handlers.websocket'));
        $this->post('/apps/{appId}/events', config('websockets.handlers.trigger_event'));
        $this->get('/apps/{appId}/channels', config('websockets.handlers.fetch_channels'));
        $this->get('/apps/{appId}/channels/{channelName}', config('websockets.handlers.fetch_channel'));
        $this->get('/apps/{appId}/channels/{channelName}/users', config('websockets.handlers.fetch_users'));
        $this->get('/health', config('websockets.handlers.health'));

        $this->registerCustomRoutes();
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
     * Add a new custom route. Registered routes
     * will be resolved at server spin-up.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  string  $action
     * @return void
     */
    public function addCustomRoute(string $method, $uri, $action)
    {
        $this->customRoutes[strtolower($method)]->put($uri, $action);
    }

    /**
     * Register the custom routes into the main RouteCollection.
     *
     * @return void
     */
    public function registerCustomRoutes()
    {
        foreach ($this->customRoutes as $method => $actions) {
            $actions->each(function ($action, $uri) use ($method) {
                $this->{$method}($uri, $action);
            });
        }
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
