<?php

namespace BeyondCode\LaravelWebSockets\LaravelEcho\Http\Controllers;

use BeyondCode\LaravelWebSockets\ClientProviders\Client;
use BeyondCode\LaravelWebSockets\QueryParameters;
use Exception;
use Illuminate\Http\Request;
use GuzzleHttp\Psr7 as gPsr;
use GuzzleHttp\Psr7\Response;
use MongoDB\Driver\Query;
use Ratchet\ConnectionInterface;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Psr7\ServerRequest;
use Ratchet\Http\HttpServerInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager;

abstract class EchoController implements HttpServerInterface
{
    /** @var \BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager */
    protected $channelManager;

    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    public function onOpen(ConnectionInterface $connection, RequestInterface $request = null)
    {
        $serverRequest = (new ServerRequest(
            $request->getMethod(),
            $request->getUri(),
            $request->getHeaders(),
            $request->getBody(),
            $request->getProtocolVersion()
        ))->withQueryParams(QueryParameters::create($request)->all());

        $laravelRequest = Request::createFromBase((new HttpFoundationFactory)->createRequest($serverRequest));

        $this
            ->ensureValidAppId($laravelRequest->appId)
            ->ensureValidSignature($laravelRequest);

        $response = $this($laravelRequest);

        $connection->send(JsonResponse::create($response)->send());
        $connection->close();
    }

    function onMessage(ConnectionInterface $from, $msg)
    {
    }

    function onClose(ConnectionInterface $connection)
    {
    }

    function onError(ConnectionInterface $connection, Exception $exception)
    {
        if (! $exception instanceof HttpException) {
            return;
        }

        $response = new Response($exception->getStatusCode(), [
            'Content-Type' => 'application/json'
        ], json_encode([
            'error' => $exception->getMessage()
        ]));

        $connection->send(Psr\str($response));
        $connection->close();
    }

    public function ensureValidAppId(string $appId)
    {
        if (!$client = Client::findByAppId($appId)) {
            throw new HttpException(401, "Unknown app id `{$appId}` provided.");
        }

        return $this;
    }

    protected function ensureValidSignature(Request $request)
    {
        $bodyMd5 = md5($request->getContent());

        $signature =
            "{$request->getMethod()}\n/{$request->path()}\n" .
            "auth_key={$request->get('auth_key')}" .
            "&auth_timestamp={$request->get('auth_timestamp')}" .
            "&auth_version={$request->get('auth_version')}" .
            "&body_md5={$bodyMd5}";

        $authSignature = hash_hmac('sha256', $signature, Client::findByAppId($request->get('appId'))->appSecret);

        if ($authSignature !== $request->get('auth_signature')) {
            throw new HttpException(401, 'Invalid auth signature provided.');
        }

        return $this;
    }

    abstract public function __invoke(Request $request);
}