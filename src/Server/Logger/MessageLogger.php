<?php
/**
 * Created by PhpStorm.
 * User: freek
 * Date: 2018-11-23
 * Time: 22:01
 */

namespace BeyondCode\LaravelWebSockets\Server\Logger;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class MessageLogger extends Logger implements MessageComponentInterface
{
    /** @var \Ratchet\Http\HttpServerInterface */
    protected $app;

    public static function decorate(MessageComponentInterface $app): MessageLogger
    {
        $logger = app(MessageLogger::class);

        return $logger->setApp($app);
    }

    public function setApp(MessageComponentInterface $app)
    {
        $this->app = $app;

        return $this;
    }

    public function onOpen(ConnectionInterface $connection)
    {
        $request = $connection->httpRequest;

        $queryParameters = [];
        parse_str($request->getUri()->getQuery(), $queryParameters);

        $this->warn("New connection opened for app key {$queryParameters['appKey']}.");

        $this->app->onOpen(ConnectionLogger::decorate($connection));
    }

    public function onMessage(ConnectionInterface $connection, MessageInterface $message)
    {
        $this->info("{$connection->client->appId}: connection id {$connection->socketId} received message: {$message->getPayload()}.");

        $this->app->onMessage(ConnectionLogger::decorate($connection), $message);
    }

    public function onClose(ConnectionInterface $connection)
    {
        $this->warn("{$connection->client->appId}: connection id {$connection->socketId} closed.");

        $this->app->onClose(ConnectionLogger::decorate($connection));
    }

    public function onError(ConnectionInterface $connection, Exception $exception)
    {
        $exceptionClass = get_class($exception);

        $appId = $connection->client->appId ?? 'Unknown app id';

        $message = "{$appId}: exception `{$exceptionClass}` thrown: `{$exception->getMessage()}`";

        if ($this->verbose) {
            $message .= $exception->getTraceAsString();
        }

        $this->error($message);

        $this->app->onError(ConnectionLogger::decorate($connection), $exception);
    }

}