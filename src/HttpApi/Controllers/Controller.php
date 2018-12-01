<?php

namespace BeyondCode\LaravelWebSockets\HttpApi\Controllers;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\Events\ExceptionThrown;
use BeyondCode\LaravelWebSockets\QueryParameters;
use Exception;
use Illuminate\Http\Request;
use GuzzleHttp\Psr7\Response;
use Ratchet\ConnectionInterface;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Psr7\ServerRequest;
use Ratchet\Http\HttpServerInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;

abstract class Controller implements HttpServerInterface
{
    /** @var \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager */
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

        $connection->send(JsonResponse::create($response));
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

        $connection->send(\GuzzleHttp\Psr7\str($response));

        $connection->close();
    }

    public function ensureValidAppId(string $appId)
    {
        if (! App::findById($appId)) {
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

        $authSignature = hash_hmac('sha256', $signature, App::findById($request->get('appId'))->appSecret);

        if ($authSignature !== $request->get('auth_signature')) {
            throw new HttpException(401, 'Invalid auth signature provided.');
        }

        return $this;
    }

    abstract public function __invoke(Request $request);
}