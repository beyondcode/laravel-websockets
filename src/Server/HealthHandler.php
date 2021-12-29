<?php

namespace BeyondCode\LaravelWebSockets\Server;

use Exception;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;

class HealthHandler implements HttpServerInterface
{
    /**
     * Handle the socket opening.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \Psr\Http\Message\RequestInterface  $request
     * @return void
     */
    public function onOpen(ConnectionInterface $connection, RequestInterface $request = null)
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['ok' => true])
        );

        tap($connection)->send(Message::toString($response))->close();
    }

    /**
     * Handle the incoming message.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  string  $message
     * @return void
     */
    public function onMessage(ConnectionInterface $connection, $message)
    {
        //
    }

    /**
     * Handle the websocket close.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function onClose(ConnectionInterface $connection)
    {
        //
    }

    /**
     * Handle the websocket errors.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  WebSocketException  $exception
     * @return void
     */
    public function onError(ConnectionInterface $connection, Exception $exception)
    {
        //
    }
}
