<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Http\Controllers;

use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Psr7\ServerRequest;
use Ratchet\Http\HttpServerInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

abstract class EchoController implements HttpServerInterface
{
    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {
        $queryParameters = [];
        parse_str($request->getUri()->getQuery(), $queryParameters);

        $serverRequest = (new ServerRequest(
            $request->getMethod(),
            $request->getUri(),
            $request->getHeaders(),
            $request->getBody(),
            $request->getProtocolVersion()
        ))->withQueryParams($queryParameters);

        $response = $this(Request::createFromBase((new HttpFoundationFactory)->createRequest($serverRequest)));

        $conn->send(JsonResponse::create($response)->send());
        $conn->close();
    }

    function onMessage(ConnectionInterface $from, $msg)
    {
    }

    function onClose(ConnectionInterface $conn)
    {
    }

    function onError(ConnectionInterface $conn, \Exception $e)
    {
    }

    abstract public function __invoke(Request $request);
}