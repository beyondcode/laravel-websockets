<?php

namespace BeyondCode\LaravelWebSockets\Server;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Contracts\ChannelManager;
use BeyondCode\LaravelWebSockets\DashboardLogger;
use BeyondCode\LaravelWebSockets\Events\ConnectionClosed;
use BeyondCode\LaravelWebSockets\Events\NewConnection;
use BeyondCode\LaravelWebSockets\Events\WebSocketMessageReceived;
use BeyondCode\LaravelWebSockets\Facades\StatisticsCollector;
use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class WebSocketHandler implements MessageComponentInterface
{
    /**
     * The channel manager.
     *
     * @var ChannelManager
     */
    protected $channelManager;

    /**
     * Initialize a new handler.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\ChannelManager  $channelManager
     * @return void
     */
    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    /**
     * Handle the socket opening.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function onOpen(ConnectionInterface $connection)
    {
        if (! $this->connectionCanBeMade($connection)) {
            return $connection->close();
        }

        $this->verifyAppKey($connection)
            ->verifyOrigin($connection)
            ->limitConcurrentConnections($connection)
            ->generateSocketId($connection)
            ->establishConnection($connection);

        if (isset($connection->app)) {
            /** @var \GuzzleHttp\Psr7\Request $request */
            $request = $connection->httpRequest;

            if ($connection->app->statisticsEnabled) {
                StatisticsCollector::connection($connection->app->id);
            }

            $this->channelManager->subscribeToApp($connection->app->id);

            $this->channelManager->connectionPonged($connection);

            DashboardLogger::log($connection->app->id, DashboardLogger::TYPE_CONNECTED, [
                'origin' => "{$request->getUri()->getScheme()}://{$request->getUri()->getHost()}",
                'socketId' => $connection->socketId,
            ]);

            NewConnection::dispatch($connection->app->id, $connection->socketId);
        }
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
        if (! isset($connection->app)) {
            return;
        }

        Messages\PusherMessageFactory::createForMessage(
            $message, $connection, $this->channelManager
        )->respond();

        if ($connection->app->statisticsEnabled) {
            StatisticsCollector::webSocketMessage($connection->app->id);
        }

        WebSocketMessageReceived::dispatch(
            $connection->app->id,
            $connection->socketId,
            $message
        );
    }

    /**
     * Handle the websocket close.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function onClose(ConnectionInterface $connection)
    {
        $this->channelManager
            ->unsubscribeFromAllChannels($connection)
            ->then(function (bool $unsubscribed) use ($connection) {
                if (isset($connection->app)) {
                    if ($connection->app->statisticsEnabled) {
                        StatisticsCollector::disconnection($connection->app->id);
                    }

                    $this->channelManager->unsubscribeFromApp($connection->app->id);

                    DashboardLogger::log($connection->app->id, DashboardLogger::TYPE_DISCONNECTED, [
                        'socketId' => $connection->socketId,
                    ]);

                    ConnectionClosed::dispatch($connection->app->id, $connection->socketId);
                }
            });
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
        if ($exception instanceof Exceptions\WebSocketException) {
            $connection->send(json_encode(
                $exception->getPayload()
            ));
        }
    }

    /**
     * Check if the connection can be made for the
     * current server instance.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return bool
     */
    protected function connectionCanBeMade(ConnectionInterface $connection): bool
    {
        return $this->channelManager->acceptsNewConnections();
    }

    /**
     * Verify the app key validity.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return $this
     */
    protected function verifyAppKey(ConnectionInterface $connection)
    {
        $query = QueryParameters::create($connection->httpRequest);

        $appKey = $query->get('appKey');

        if (! $app = App::findByKey($appKey)) {
            throw new Exceptions\UnknownAppKey($appKey);
        }

        $connection->app = $app;

        return $this;
    }

    /**
     * Verify the origin.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return $this
     */
    protected function verifyOrigin(ConnectionInterface $connection)
    {
        if (! $connection->app->allowedOrigins) {
            return $this;
        }

        $header = (string) ($connection->httpRequest->getHeader('Origin')[0] ?? null);

        $origin = parse_url($header, PHP_URL_HOST) ?: $header;

        if (! $header || ! in_array($origin, $connection->app->allowedOrigins)) {
            throw new Exceptions\OriginNotAllowed($connection->app->key);
        }

        return $this;
    }

    /**
     * Limit the connections count by the app.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return $this
     */
    protected function limitConcurrentConnections(ConnectionInterface $connection)
    {
        if (! is_null($capacity = $connection->app->capacity)) {
            $this->channelManager
                ->getGlobalConnectionsCount($connection->app->id)
                ->then(function ($connectionsCount) use ($capacity, $connection) {
                    if ($connectionsCount >= $capacity) {
                        $exception = new Exceptions\ConnectionsOverCapacity;

                        $payload = json_encode($exception->getPayload());

                        tap($connection)->send($payload)->close();
                    }
                });
        }

        return $this;
    }

    /**
     * Create a socket id.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return $this
     */
    protected function generateSocketId(ConnectionInterface $connection)
    {
        $socketId = sprintf('%d.%d', random_int(1, 1000000000), random_int(1, 1000000000));

        $connection->socketId = $socketId;

        return $this;
    }

    /**
     * Establish connection with the client.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return $this
     */
    protected function establishConnection(ConnectionInterface $connection)
    {
        $connection->send(json_encode([
            'event' => 'pusher:connection_established',
            'data' => json_encode([
                'socket_id' => $connection->socketId,
                'activity_timeout' => 30,
            ]),
        ]));

        return $this;
    }
}
