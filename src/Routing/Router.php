<?php

namespace BeyondCode\LaravelWebSockets\Routing;

use ArrayObject;
use BeyondCode\LaravelWebSockets\Contracts\ClientMessage;
use BeyondCode\LaravelWebSockets\Contracts\Loop;
use BeyondCode\LaravelWebSockets\Contracts\Message;
use BeyondCode\LaravelWebSockets\Contracts\Promise;
use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\MiddlewareNameResolver;
use Illuminate\Routing\SortedMiddleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use JsonSerializable;
use Psr\Http\Message\StreamInterface;
use ReflectionClass;
use RuntimeException;

use function collect;
use function is_object;
use function spl_object_id;

/**
 * This is also a Laravel's Router copy, adapted to handle JSON messages instead of HTTP Requests.
 */
class Router
{
    /**
     * Application container.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The collection of routes.
     *
     * @var \Illuminate\Support\Collection<\Illuminate\Routing\Route>
     */
    protected $routes;

    /**
     * The route group attribute stack.
     *
     * @var array
     */
    protected $groupStack = [];

    /**
     * All of the short-hand keys for middlewares.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * All of the middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [];

    /**
     * The priority-sorted list of middleware.
     *
     * Forces the listed middleware to always be in the given order.
     *
     * @var array
     */
    public $middlewarePriority = [];

    /**
     * Loop implementation.
     *
     * @var \BeyondCode\LaravelWebSockets\Contracts\Loop
     */
    protected $loop;

    /**
     * Router constructor.
     */
    public function __construct(Container $container, Loop $loop)
    {
        $this->container = $container;
        $this->loop = $loop;
        $this->routes = collect();
    }

    /**
     * Adds a new Route into the Router.
     *
     * @param  string  $service
     * @param  callable|string  $action
     *
     * @return \BeyondCode\LaravelWebSockets\Http\Router\Router
     */
    public function add(string $service, $action): Router
    {
        if ($this->actionReferencesController($action)) {
            $action = $this->convertToControllerAction($action);
        }

        $this->routes->put($service, $this->newRoute($service, $action));

        return $this;
    }

    /**
     * Determine if the action is routing to a controller.
     *
     * @param  mixed  $action
     * @return bool
     */
    protected function actionReferencesController($action): bool
    {
        if (! $action instanceof Closure) {
            return is_string($action) || (isset($action['uses']) && is_string($action['uses']));
        }

        return false;
    }

    /**
     * Add a controller based route action to the action array.
     *
     * @param  array|string  $action
     * @return array
     */
    protected function convertToControllerAction($action)
    {
        if (is_string($action)) {
            $action = ['uses' => $action];
        }

        // Here we'll merge any group "uses" statement if necessary so that the action
        // has the proper clause for this property. Then we can simply set the name
        // of the controller on the action and return the action array for usage.
        if ($this->hasGroupStack()) {
            $action['uses'] = $this->prependGroupNamespace($action['uses']);
        }

        // Here we will set this controller name on the action array just so we always
        // have a copy of it for reference if we need it. This can be used while we
        // search for a controller name or do some other type of fetch operation.
        $action['controller'] = $action['uses'];

        return $action;
    }

    /**
     * Create a new Route object.
     *
     * @param  string  $service
     * @param  mixed  $action
     *
     * @return \BeyondCode\LaravelWebSockets\Routing\Route
     */
    public function newRoute(string $service, $action): Route
    {
        return (new Route($service, $action))
            ->setRouter($this)
            ->setContainer($this->container);
    }

    /**
     * Determine if the router currently has a group stack.
     *
     * @return bool
     */
    public function hasGroupStack(): bool
    {
        return ! empty($this->groupStack);
    }

    /**
     * Prepend the last group namespace onto the use clause.
     *
     * @param  string  $class
     * @return string
     */
    protected function prependGroupNamespace(string $class): string
    {
        $group = end($this->groupStack);

        return isset($group['namespace']) && strpos($class, '\\') !== 0
            ? $group['namespace'].'\\'.$class : $class;
    }

    /**
     * Matches the route,
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Message  $message
     *
     * @return \BeyondCode\LaravelWebSockets\Contracts\Promise<string|false>
     */
    public function route(Message $message): Promise
    {
        // If the message it's a binary message we will defer all content reading
        // and leave it to the default binary controller action since its unique
        // for a server. Otherwise, we will warm the text content and route it.
        $route = $message->isBinary()
            ? $this->defaultBinaryAction()
            : $this->routes->firstWhere('name', $message->json('service', 'not_found'));

        return $this->loop->new(function () use ($route, $message) {
            return $this->runRouteWithStack($route, $message);
        });
    }

    /**
     * Transforms the controller response, to a message, if there is any.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Message  $message
     * @param mixed|null $response
     *
     * @return false|string
     */
    public function toMessage(Message $message, $response)
    {
        if ($response instanceof Model) {
            $response = $response->toJson();
        } elseif ($response instanceof Stringable) {
            $response = $response->__toString();
        } elseif ($response instanceof Arrayable ||
                $response instanceof Jsonable ||
                $response instanceof ArrayObject ||
                $response instanceof JsonSerializable ||
                is_array($response)) {
            $response = json_encode($response);
        } elseif($response instanceof StreamInterface) {
            // TODO: Transform the response into a stream to the user.
            throw new RuntimeException('Streaming not supported... yet.');
        }

        return $response;
    }

    /**
     * Returns the default binary action for handling binary messages.
     *
     * @return \BeyondCode\LaravelWebSockets\Routing\Route
     */
    protected function defaultBinaryAction(): Route
    {
        if (! $binary = $this->routes->firstWhere('name', 'binary')) {
            throw new RuntimeException('No default handler for binary messages.');
        }

        return $binary;
    }

    /**
     * Runs the route with the Message and the Client
     *
     * @param  \BeyondCode\LaravelWebSockets\Routing\Route  $route
     * @param  \BeyondCode\LaravelWebSockets\Contracts\ClientMessage  $message
     *
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function runRouteWithStack(Route $route, ClientMessage $message)
    {
        $shouldSkipMiddleware = $this->container->bound('middleware.disable') &&
            $this->container->make('middleware.disable') === true;

        $middleware = $shouldSkipMiddleware ? [] : $this->gatherRouteMiddleware($route);

        return (new Pipeline($this->container))
            ->send($message)
            ->through($middleware)
            ->then(function (Message $message) use ($route) {
                return $this->toMessage($message, $route->run());
            });
    }

    /**
     * Gather the middleware for the given route with resolved class names.
     *
     * @param  \BeyondCode\LaravelWebSockets\Routing\Route  $route
     *
     * @return array
     */
    public function gatherRouteMiddleware(Route $route): array
    {
        $excluded = collect($route->excludedMiddleware())->map(function ($name) {
            return (array) MiddlewareNameResolver::resolve($name, $this->middleware, $this->middlewareGroups);
        })->flatten()->values()->all();

        $middleware = collect($route->gatherMiddleware())->map(function ($name) {
            return (array) MiddlewareNameResolver::resolve($name, $this->middleware, $this->middlewareGroups);
        })->flatten()->reject(function ($name) use ($excluded) {
            if (empty($excluded)) {
                return false;
            }

            if ($name instanceof Closure) {
                return false;
            }

            if (in_array($name, $excluded, true)) {
                return true;
            }

            if (! class_exists($name)) {
                return false;
            }

            $reflection = new ReflectionClass($name);

            return collect($excluded)->contains(function ($exclude) use ($reflection) {
                return class_exists($exclude) && $reflection->isSubclassOf($exclude);
            });
        })->values();

        return $this->sortMiddleware($middleware);
    }

    /**
     * Sort the given middleware by priority.
     *
     * @param  \Illuminate\Support\Collection  $middlewares
     * @return array
     */
    protected function sortMiddleware(Collection $middlewares): array
    {
        return (new SortedMiddleware($this->middlewarePriority, $middlewares))->all();
    }

    /**
     * Get the current group stack for the router.
     *
     * @return array
     */
    public function getGroupStack(): array
    {
        return $this->groupStack;
    }

    /**
     * Remove any duplicate middleware from the given array.
     *
     * @param  array  $middleware
     * @return array
     */
    public static function uniqueMiddleware(array $middleware): array
    {
        $seen = [];
        $result = [];

        foreach ($middleware as $value) {
            $key = is_object($value) ? spl_object_id($value) : $value;

            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $value;
            }
        }

        return $result;
    }
}
