<?php

namespace BeyondCode\LaravelWebSockets\Server\Logger;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\WebSocket\WsServerInterface;

class HttpLogger extends Logger implements MessageComponentInterface, WsServerInterface
{
    /** @var \Ratchet\Http\HttpServerInterface */
    protected $app;

    public static function decorate(MessageComponentInterface $app): self
    {
        $logger = app(self::class);

        return $logger->setApp($app);
    }

    public function setApp(MessageComponentInterface $app)
    {
        $this->app = $app;

        return $this;
    }

    public function getSubProtocols()
    {
        if ($this->app instanceof WsServerInterface) {
            return $this->app->getSubProtocols();
        } else {
            return [];
        }
    }

    public function onOpen(ConnectionInterface $connection)
    {
        $this->app->onOpen($connection);
    }

    public function onMessage(ConnectionInterface $connection, $message)
    {
        $this->app->onMessage($connection, $message);
    }

    public function onClose(ConnectionInterface $connection)
    {
        $this->app->onClose($connection);
    }

    public function onError(ConnectionInterface $connection, Exception $exception)
    {
        $exceptionClass = get_class($exception);

        $message = "Exception `{$exceptionClass}` thrown: `{$exception->getMessage()}`";

        if ($this->verbose) {
            $message .= $exception->getTraceAsString();
        }

        $this->error($message);

        $this->app->onError($connection, $exception);
    }
}
