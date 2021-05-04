<?php

namespace BeyondCode\LaravelWebSockets\Routing;

use Illuminate\Container\Container;
use Illuminate\Routing\Controller;
use Illuminate\Routing\RouteDependencyResolverTrait;

use function collect;

class WebsocketControllerDispatcher
{
    use RouteDependencyResolverTrait;

    /**
     * Application container.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * WebsocketControllerDispatcher constructor.
     *
     * @param  \Illuminate\Container\Container  $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Dispatch a request to a given controller and method.
     *
     * @param  mixed  $controller
     * @param  string  $method
     *
     * @return mixed
     */
    public function dispatch($controller, string $method)
    {
        $parameters = $this->resolveClassMethodDependencies([], $controller, $method);

        if (method_exists($controller, 'callAction')) {
            return $controller->callAction($method, $parameters);
        }

        return $controller->{$method}(...array_values($parameters));
    }

    /**
     * Get the middleware for the controller instance.
     *
     * @param  \Illuminate\Routing\Controller  $controller
     * @param  string  $method
     *
     * @return array
     */
    public function getMiddleware(Controller $controller, string $method): array
    {
        if (! method_exists($controller, 'getMiddleware')) {
            return [];
        }

        return collect($controller->getMiddleware())->reject(function ($data) use ($method) {
            return static::methodExcludedByOptions($method, $data['options']);
        })->pluck('middleware')->all();
    }

    /**
     * Determine if the given options exclude a particular method.
     *
     * @param  string  $method
     * @param  array  $options
     *
     * @return bool
     */
    protected static function methodExcludedByOptions(string $method, array $options): bool
    {
        return (isset($options['only']) && !in_array($method, (array)$options['only'], true)) ||
            (! empty($options['except']) && in_array($method, (array)$options['except'], true));
    }
}
