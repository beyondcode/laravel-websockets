<?php

namespace BeyondCode\LaravelWebSockets\Server;

use Ratchet\Http\HttpServerInterface;

class HttpServer extends \Ratchet\Http\HttpServer
{
    public function __construct(HttpServerInterface $component, int $maxRequestSize = 4096)
    {
        parent::__construct($component);

        $this->_reqParser->maxSize = $maxRequestSize;
    }
}
