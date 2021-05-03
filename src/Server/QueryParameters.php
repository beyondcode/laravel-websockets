<?php

namespace BeyondCode\LaravelWebSockets\Server;

use Psr\Http\Message\RequestInterface;

class QueryParameters
{
    /**
     * The Request object.
     *
     * @var \Psr\Http\Message\RequestInterface
     */
    protected $request;

    /**
     * Initialize the class.
     *
     * @param  \Psr\Http\Message\RequestInterface  $request
     * @return void
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Get all query parameters.
     *
     * @return array
     */
    public function all(): array
    {
        $queryParameters = [];

        parse_str($this->request->getUri()->getQuery(), $queryParameters);

        return $queryParameters;
    }

    /**
     * Get a specific query parameter.
     *
     * @param  string  $name
     * @return string
     */
    public function get(string $name): string
    {
        return $this->all()[$name] ?? '';
    }

    /**
     * Creates a new Query Parameter instance.
     *
     * @param  \Psr\Http\Message\RequestInterface  $request
     *
     * @return static
     */
    public static function create(RequestInterface $request): QueryParameters
    {
        return new static($request);
    }
}
