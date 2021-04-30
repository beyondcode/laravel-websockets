<?php

namespace BeyondCode\LaravelWebSockets\Server;

use Ratchet\Http\HttpServer as BaseHttpServer;
use Ratchet\Http\HttpServerInterface;

class HttpServer extends BaseHttpServer
{
    /**
     * Create a new server instance.
     *
     * @param  \Ratchet\Http\HttpServerInterface  $component
     * @param  int  $maxRequestSize
     * @return void
     */
    public function __construct(HttpServerInterface $component, int $maxRequestSize = 4096)
    {
        parent::__construct($component);

        $this->_reqParser->maxSize = $maxRequestSize;
    }
}
