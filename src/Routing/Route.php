<?php

namespace BeyondCode\LaravelWebSockets\Routing;

use BeyondCode\LaravelWebSockets\Contracts\Message;
use Illuminate\Container\Container as LaravelContainer;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\CreatesRegularExpressionRouteConstraints;
use Illuminate\Routing\RouteAction;
use Illuminate\Routing\RouteDependencyResolverTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use ReflectionFunction;

use function last;

/**
 * This is just a blatant copy of Laravel's Route, but without a lot of methods that don't make sense
 * for routing JSON messages to controllers.
 */
class Route
{
    use CreatesRegularExpressionRouteConstraints, Macroable, RouteDependencyResolverTrait;

    /**
     * The service name the route responds to.
     *
     * @var string
     */
    public $service;

    /**
     * The route action array, as a route can respond many actions.
     *
     * @var array
     */
    public $action;

    /**
     * The controller instance.
     *
     * @var mixed
     */
    public $controller;

    /**
     * The computed gathered middleware.
     *
     * @var array|null
     */
    public $computedMiddleware;

    /**
     * The route group attribute stack.
     *
     * @var array
     */
    protected $groupStack = [];

    /**
     * The router instance used by the route.
     *
     * @var \BeyondCode\LaravelWebSockets\Routing\Router
     */
    protected $router;

    /**
     * The container instance used by the route.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Create a new Route instance.
     *
     * @param  string  $service
     * @param  callable|array|null  $action
     */
    public function __construct(string $service, $action)
    {
        $this->service = $service;
        $this->action = $this->parseAction($action);
    }

    /**
     * Parse the route action into a standard array.
     *
     * @param  callable|array|null  $action
     *
     * @return array
     *
     * @throws \UnexpectedValueException
     */
    protected function parseAction($action): array
    {
        return RouteAction::parse($this->service, $action);
    }

    /**
     * Set the router instance on the route.
     *
     * @param  \BeyondCode\LaravelWebSockets\Routing\Router  $router
     *
     * @return $this
     */
    public function setRouter(Router $router): Route
    {
        $this->router = $router;

        return $this;
    }

    /**
     * Set the container instance on the route.
     *
     * @param  LaravelContainer  $container
     *
     * @return $this
     */
    public function setContainer(Container $container): Route
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Set the URI that the route responds to.
     *
     * @param $service
     *
     * @return $this
     */
    public function setService($service): Route
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get the URI associated with the route.
     *
     * @return string
     */
    public function service(): string
    {
        return $this->service;
    }

    /**
     * Determine whether the route's service matches the given patterns.
     *
     * @param  mixed  ...$patterns
     *
     * @return bool
     */
    public function serviceMatches(...$patterns): bool
    {
        if (is_null($routeName = $this->service)) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds a handler for the route.
     *
     * @param  \Closure|array|string  $action
     *
     * @return $this
     */
    public function uses($action): Route
    {
        if (is_array($action)) {
            $action = $action[0] . '@' . $action[1];
        }

        $action = is_string($action) ? $this->addGroupNamespaceToStringUses($action) : $action;

        return $this->setAction(
            array_merge($this->action, $this->parseAction(['uses' => $action, 'controller' => $action]))
        );
    }

    /**
     * Set the action array for the route.
     *
     * @param  array  $action
     *
     * @return $this
     */
    public function setAction(array $action): Route
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Run the route action and return the response.
     *
     * @return mixed
     */
    public function run()
    {
        $this->container = $this->container ?: new LaravelContainer();

        try {
            if ($this->isControllerAction()) {
                return $this->runController();
            }

            return $this->runCallable();
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Run the route action and return the response.
     *
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function runController()
    {
        return $this->controllerDispatcher()->dispatch(
            $this->getController(), $this->getControllerMethod()
        );
    }

    /**
     * Run the route action and return the response.
     *
     * @return mixed
     */
    protected function runCallable()
    {
        $callable = $this->action['uses'];

        return $callable(...array_values($this->resolveMethodDependencies(
            [], new ReflectionFunction($callable)
        )));
    }

    /**
     * Parse a string based action for the "uses" fluent method.
     *
     * @param  string  $action
     *
     * @return string
     */
    protected function addGroupNamespaceToStringUses(string $action): string
    {
        $groupStack = last($this->router->getGroupStack());

        if (isset($groupStack['namespace']) && strpos($action, '\\') !== 0) {
            return $groupStack['namespace'] . '\\' . $action;
        }

        return $action;
    }
    /**
     * Get all middleware, including the ones from the controller.
     *
     * @return array
     */
    public function gatherMiddleware(): ?array
    {
        if (! is_null($this->computedMiddleware)) {
            return $this->computedMiddleware;
        }

        $this->computedMiddleware = [];

        return $this->computedMiddleware = Router::uniqueMiddleware(
            array_merge($this->middleware(), $this->controllerMiddleware())
        );
    }


    /**
     * Get or set the middlewares attached to the route.
     *
     * @param  array|string|null  $middleware
     * @return $this|array
     */
    public function middleware($middleware = null)
    {
        if (is_null($middleware)) {
            return (array) ($this->action['middleware'] ?? []);
        }

        if (is_string($middleware)) {
            $middleware = func_get_args();
        }

        $this->action['middleware'] = array_merge(
            (array) ($this->action['middleware'] ?? []), $middleware
        );

        return $this;
    }

    /**
     * Get the middleware for the route's controller.
     *
     * @return array
     */
    public function controllerMiddleware(): array
    {
        if (! $this->isControllerAction()) {
            return [];
        }

        return $this->controllerDispatcher()->getMiddleware(
            $this->getController(), $this->getControllerMethod()
        );
    }

    /**
     * Specify middleware that should be removed from the given route.
     *
     * @param  array|string  $middleware
     *
     * @return $this
     */
    public function withoutMiddleware($middleware): Route
    {
        $this->action['excluded_middleware'] = array_merge(
            (array) ($this->action['excluded_middleware'] ?? []), Arr::wrap($middleware)
        );

        return $this;
    }

    /**
     * Get the middleware should be removed from the route.
     *
     * @return array
     */
    public function excludedMiddleware(): array
    {
        return (array) ($this->action['excluded_middleware'] ?? []);
    }


    /**
     * Checks whether the route's action is a controller.
     *
     * @return bool
     */
    protected function isControllerAction(): bool
    {
        return is_string($this->action['uses']) && is_callable($this->action['uses']);
    }

    /**
     * Get the dispatcher for the route's controller.
     *
     * @return \BeyondCode\LaravelWebSockets\Routing\WebsocketControllerDispatcher
     */
    public function controllerDispatcher(): WebsocketControllerDispatcher
    {
        return new WebsocketControllerDispatcher($this->container);
    }

    /**
     * Get the controller instance for the route.
     *
     * @return mixed
     */
    public function getController()
    {
        if (! $this->controller) {
            $class = $this->parseControllerCallback()[0];

            $this->controller = $this->container->make(ltrim($class, '\\'));
        }

        return $this->controller;
    }

    /**
     * Parse the controller.
     *
     * @return array
     */
    protected function parseControllerCallback(): array
    {
        return Str::parseCallback($this->action['uses']);
    }

    /**
     * Get the controller method used for the route.
     *
     * @return string
     */
    protected function getControllerMethod(): string
    {
        return $this->parseControllerCallback()[1];
    }

    /**
     * Determine if the route matches a given request.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Message  $message
     *
     * @return bool
     */
    public function matches(Message $message): bool
    {
        return $message->json('service') === $this->service;
    }
}
