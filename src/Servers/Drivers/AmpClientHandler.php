<?php

namespace BeyondCode\LaravelWebSockets\Servers\Drivers;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Promise;
use Amp\Success;
use Amp\Websocket\Client;
use Amp\Websocket\Server\ClientHandler;
use Amp\Websocket\Server\Gateway;
use BeyondCode\LaravelWebSockets\Contracts\AppManager;
use BeyondCode\LaravelWebSockets\Routing\Router;
use Generator;

use function Amp\call;
use function in_array;

class AmpClientHandler implements ClientHandler
{
    /**
     * Origins accepted for establishing a connection.
     *
     * @var array|string[]
     */
    protected $origins = [];

    /**
     * Router for incoming messages.
     *
     * @var \BeyondCode\LaravelWebSockets\Routing\Router
     */
    protected $router;
    /**
     * @var \BeyondCode\LaravelWebSockets\Contracts\AppManager
     */
    protected $appManager;

    /**
     * AmpClientHandler constructor.
     *
     * @param  \BeyondCode\LaravelWebSockets\Routing\Router  $router
     * @param  \BeyondCode\LaravelWebSockets\Contracts\AppManager  $appManager
     */
    public function __construct(Router $router, AppManager $appManager)
    {
        $this->router = $router;
        $this->appManager = $appManager;
    }

    /**
     * Sets the accepted origins for Websocket connections.
     *
     * @param  array|string[]  $origins
     *
     * @return AmpClientHandler
     */
    public function setOrigins(array $origins): AmpClientHandler
    {
        $this->origins = $origins;

        return $this;
    }

    /**
     * Respond to websocket handshake requests.
     *
     * @param  Gateway  $gateway  The associated websocket endpoint to which the client is connecting.
     * @param  Request  $request  The HTTP request that instigated the handshake
     * @param  Response  $response  The switching protocol response for adding headers, etc.
     *
     * @return Promise<Response> Resolve the Promise with a Response set to a status code
     *                           other than {@link Status::SWITCHING_PROTOCOLS} to deny the
     *                           handshake Request.
     */
    public function handleHandshake(Gateway $gateway, Request $request, Response $response): Promise
    {
        if (!empty($this->origins) && !in_array($request->getHeader('origin'), $this->origins, true)) {
            return $gateway->getErrorHandler()->handleError(403);
        }

        return new Success($response);
    }

    /**
     * This method is called when a new websocket connection is established on the endpoint.
     *
     * @param  Gateway  $gateway  The associated websocket endpoint to which the client is connected.
     * @param  Client  $client  The websocket client connection.
     * @param  Request  $request  The HTTP request that instigated the connection.
     * @param  Response  $response  The HTTP response sent to client to accept the connection.
     *
     * @return Promise<void>
     */
    public function handleClient(Gateway $gateway, Client $client, Request $request, Response $response): Promise
    {
        return call([$this, 'sendClientMessagesThroughRouter'], new AmpClient($client));
    }

    /**
     * Receive messages from the client and route them.
     *
     * @param  \BeyondCode\LaravelWebSockets\Servers\Drivers\AmpClient  $client
     *
     * @return \Generator
     */
    public function sendClientMessagesThroughRouter(AmpClient $client): Generator
    {
        /** @var \BeyondCode\LaravelWebSockets\Servers\Drivers\AmpMessage $message */
        while ($message = yield $client->receive()) {
            if ($response = yield $this->router->route($message)) {
                yield $client->getClient()->send($response);
            }

//            $payload = yield $message->buffer();
//            yield $client->send('Message of length ' . strlen($payload) . 'received');
        }
    }
}
