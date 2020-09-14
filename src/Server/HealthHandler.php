<?php

namespace BeyondCode\LaravelWebSockets\Server;

use Exception;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class HealthHandler implements MessageComponentInterface
{
    /**
     * Handle the socket opening.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function onOpen(ConnectionInterface $connection, RequestInterface $request = null)
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['ok' => true])
        );

        tap($connection)->send(\GuzzleHttp\Psr7\str($response))->close();
    }

    /**
     * Handle the incoming message.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \Ratchet\RFC6455\Messaging\MessageInterface  $message
     * @return void
     */
    public function onMessage(ConnectionInterface $connection, MessageInterface $message)
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
