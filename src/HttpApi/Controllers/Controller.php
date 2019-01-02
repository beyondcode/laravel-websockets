<?php

namespace BeyondCode\LaravelWebSockets\HttpApi\Controllers;

use Exception;
use Pusher\Pusher;
use Illuminate\Http\Request;
use GuzzleHttp\Psr7\Response;
use Ratchet\ConnectionInterface;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Psr7\ServerRequest;
use Ratchet\Http\HttpServerInterface;
use Psr\Http\Message\RequestInterface;
use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\QueryParameters;
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

    public function onMessage(ConnectionInterface $from, $msg)
    {
    }

    public function onClose(ConnectionInterface $connection)
    {
    }

    public function onError(ConnectionInterface $connection, Exception $exception)
    {
        if (! $exception instanceof HttpException) {
            return;
        }

        $response = new Response($exception->getStatusCode(), [
            'Content-Type' => 'application/json',
        ], json_encode([
            'error' => $exception->getMessage(),
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
        /*
         * The `auth_signature` & `body_md5` parameters are not included when calculating the `auth_signature` value.
         *
         * The `appId`, `appKey` & `channelName` parameters are actually route paramaters and are never supplied by the client.
         */
        $params = array_except($request->query(), ['auth_signature', 'body_md5', 'appId', 'appKey', 'channelName']);

        if ($request->getContent() !== '') {
            $params['body_md5'] = md5($request->getContent());
        }

        ksort($params);

        $signature = "{$request->getMethod()}\n/{$request->path()}\n".Pusher::array_implode('=', '&', $params);

        $authSignature = hash_hmac('sha256', $signature, App::findById($request->get('appId'))->secret);

        if ($authSignature !== $request->get('auth_signature')) {
            throw new HttpException(401, 'Invalid auth signature provided.');
        }

        return $this;
    }

    abstract public function __invoke(Request $request);
}
