<?php

namespace BeyondCode\LaravelWebSockets;

use Psr\Http\Message\RequestInterface;

class QueryParameters
{
    /** @var \Psr\Http\Message\RequestInterface */
    protected $request;

    public static function create(RequestInterface $request)
    {
        return new static($request);
    }

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function all(): array
    {
        $queryParameters = [];

        parse_str($this->request->getUri()->getQuery(), $queryParameters);

        return $queryParameters;
    }

    public function get(string $name): string
    {
        return $this->all()[$name] ?? '';
    }
}
