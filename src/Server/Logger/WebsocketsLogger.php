<?php

namespace BeyondCode\LaravelWebSockets\Server\Logger;

use BeyondCode\LaravelWebSockets\QueryParameters;
use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class WebsocketsLogger extends Logger implements MessageComponentInterface
{
    /** @var \Ratchet\Http\HttpServerInterface */
    protected $app;

    public static function decorate(MessageComponentInterface $app): self
    {
        $logger = clone app(self::class);

        return $logger->setApp($app);
    }

    public function setApp(MessageComponentInterface $app)
    {
        $this->app = $app;

        return $this;
    }

    public function onOpen(ConnectionInterface $connection)
    {
        $appKey = QueryParameters::create($connection->httpRequest)->get('appKey');

        $this->warn("New connection opened for app key {$appKey}.");

        $this->app->onOpen(ConnectionLogger::decorate($connection));
    }

    public function onMessage(ConnectionInterface $connection, MessageInterface $message)
    {
        $this->info("{$connection->app->id}: connection id {$connection->socketId} received message: {$message->getPayload()}.");

        $this->app->onMessage(ConnectionLogger::decorate($connection), $message);
    }

    public function onClose(ConnectionInterface $connection)
    {
        $socketId = $connection->socketId ?? null;

        $this->warn("Connection id {$socketId} closed.");

        $this->app->onClose(ConnectionLogger::decorate($connection));
    }

    public function onError(ConnectionInterface $connection, Exception $exception)
    {
        $exceptionClass = get_class($exception);

        $appId = $connection->app->id ?? 'Unknown app id';

        $message = "{$appId}: exception `{$exceptionClass}` thrown: `{$exception->getMessage()}`.";

        if ($this->verbose) {
            $message .= $exception->getTraceAsString();
        }

        $this->error($message);

        $this->app->onError(ConnectionLogger::decorate($connection), $exception);
    }
}
