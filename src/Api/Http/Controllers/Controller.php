<?php

namespace BeyondCode\LaravelWebSockets\Api\Http\Controllers;

use Amp\Promise;
use Amp\Websocket\Server\Websocket;
use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Contracts\ChannelManager;
use BeyondCode\LaravelWebSockets\Contracts\Connection;
use BeyondCode\LaravelWebSockets\Server\QueryParameters;
use Exception;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Psr\Http\Message\RequestInterface;
use Pusher\Pusher;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

use function collect;
use function response;
use function tap;

abstract class Controller
{
    /**
     * The request buffer.
     *
     * @var string
     */
    protected $requestBuffer = '';

    /**
     * The incoming request.
     *
     * @var \Psr\Http\Message\RequestInterface
     */
    protected $request;

    /**
     * The content length that will
     * be calculated.
     *
     * @var int
     */
    protected $contentLength;

    /**
     * The channel manager.
     *
     * @var \BeyondCode\LaravelWebSockets\Contracts\ChannelManager
     */
    protected $channelManager;

    /**
     * The app attached with this request.
     *
     * @var \BeyondCode\LaravelWebSockets\Apps\App|null
     */
    protected $app;
    /**
     * @var \Amp\Websocket\Server\Websocket
     */
    protected $server;

    /**
     * Initialize the request.
     *
     * @param  ChannelManager  $channelManager
     * @param  \Amp\Websocket\Server\Websocket  $server
     */
    public function __construct(ChannelManager $channelManager, Websocket $server)
    {
        $this->channelManager = $channelManager;
        $this->server = $server;
    }

    /**
     * Handle the opened socket connection.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  \Psr\Http\Message\RequestInterface|null  $request
     *
     * @return void
     */
    public function onOpen(Connection $connection, RequestInterface $request = null): void
    {
        $this->request = $request;

        $this->contentLength = $this->findContentLength($this->request->getHeaders());

        $this->requestBuffer = $this->request ? (string) $this->request->getBody() : '';

        if (! $this->verifyContentLength()) {
            return;
        }

        $this->handleRequest($connection);
    }

    /**
     * Handle the oncoming message and add it to buffer.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $from
     * @param  mixed  $message
     *
     * @return void
     */
    public function onMessage(Connection $from, $message): void
    {
        $this->requestBuffer .= $message;

        if ($this->verifyContentLength()) {
            $this->handleRequest($from);
        }
    }

    /**
     * Handle the socket closing.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     *
     * @return void
     */
    public function onClose(Connection $connection): void
    {
        //
    }

    /**
     * Handle the errors.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  Exception  $exception
     *
     * @return void
     */
    public function onError(Connection $connection, Throwable $exception): void
    {
        if (! $exception instanceof HttpException) {
            return;
        }

        $response = new Response($exception->getStatusCode(), [
            'Content-Type' => 'application/json',
        ], json_encode([
            'error' => $exception->getMessage(),
        ]));

        tap($connection)->send((string)$response->getBody())->close();
    }

    /**
     * Get the content length from the headers.
     *
     * @param  array  $headers
     * @return int
     */
    protected function findContentLength(array $headers): int
    {
        return collect($headers)->first(static function (string $values, string $header): bool {
            return strtolower($header) === 'content-length';
        })[0] ?? 0;
    }

    /**
     * Check the content length.
     *
     * @return bool
     */
    protected function verifyContentLength(): bool
    {
        return strlen($this->requestBuffer) === $this->contentLength;
    }

    /**
     * Handle the oncoming connection.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     *
     * @return void
     */
    protected function handleRequest(Connection $connection): void
    {
        $serverRequest = (new ServerRequest(
            $this->request->getMethod(),
            $this->request->getUri(),
            $this->request->getHeaders(),
            $this->requestBuffer,
            $this->request->getProtocolVersion()
        ))->withQueryParams(QueryParameters::create($this->request)->all());

        $laravelRequest = Request::createFromBase((new HttpFoundationFactory)->createRequest($serverRequest));

        $this->ensureValidAppId($laravelRequest->get('appId'))
            ->ensureValidSignature($laravelRequest);

        // Invoke the controller action
        $response = $this($laravelRequest);

        // Allow for async IO in the controller action
        if ($response instanceof Promise) {
            $response->onResolve(function ($response) use ($connection): void {
                $this->sendAndClose($connection, $response);
            });

            return;
        }

        if ($response instanceof HttpException) {
            throw $response;
        }

        $this->sendAndClose($connection, $response);
    }

    /**
     * Send the response and close the connection.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\Connection  $connection
     * @param  mixed  $response
     *
     * @return void
     */
    protected function sendAndClose(Connection $connection, $response): void
    {
        tap($connection)->send(response()->json($response))->close();
    }

    /**
     * Ensure app existence.
     *
     * @param  mixed  $appId
     * @return $this
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function ensureValidAppId($appId): Controller
    {
        if (! $appId || ! $this->app = App::findById($appId)) {
            throw new HttpException(401, "Unknown app id `{$appId}` provided.");
        }

        return $this;
    }

    /**
     * Ensure signature integrity coming from an
     * authorized application.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return $this
     */
    protected function ensureValidSignature(Request $request): Controller
    {
        // The `auth_signature` & `body_md5` parameters are not included when calculating the `auth_signature` value.
        // The `appId`, `appKey` & `channelName` parameters are actually route parameters and are never supplied by the client.

        $params = Arr::except($request->query(), [
            'auth_signature', 'body_md5', 'appId', 'appKey', 'channelName',
        ]);

        if ($request->getContent() !== '') {
            $params['body_md5'] = md5($request->getContent());
        }

        ksort($params);

        $signature = "{$request->getMethod()}\n/{$request->path()}\n".Pusher::array_implode('=', '&', $params);

        $authSignature = hash_hmac('sha256', $signature, $this->app->secret);

        if ($authSignature !== $request->get('auth_signature')) {
            throw new HttpException(401, 'Invalid auth signature provided.');
        }

        return $this;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    abstract public function __invoke(Request $request);
}
