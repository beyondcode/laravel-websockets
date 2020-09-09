<?php

namespace BeyondCode\LaravelWebSockets\WebSockets;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\Facades\StatisticsLogger;
use BeyondCode\LaravelWebSockets\PubSub\ReplicationInterface;
use BeyondCode\LaravelWebSockets\QueryParameters;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\ConnectionsOverCapacity;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\OriginNotAllowed;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\UnknownAppKey;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\WebSocketException;
use BeyondCode\LaravelWebSockets\WebSockets\Messages\PusherMessageFactory;
use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use React\Promise\PromiseInterface;

class WebSocketHandler implements MessageComponentInterface
{
    /**
     * The channel manager.
     *
     * @var \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager
     */
    protected $channelManager;

    /**
     * The replicator client.
     *
     * @var ReplicationInterface
     */
    protected $replicator;

    /**
     * Initialize a new handler.
     *
     * @param  \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager  $channelManager
     * @return void
     */
    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
        $this->replicator = app(ReplicationInterface::class);
    }

    /**
     * Handle the socket opening.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function onOpen(ConnectionInterface $connection)
    {
        $this->verifyAppKey($connection)
            ->verifyOrigin($connection)
            ->limitConcurrentConnections($connection)
            ->generateSocketId($connection)
            ->establishConnection($connection);
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
        $message = PusherMessageFactory::createForMessage($message, $connection, $this->channelManager);

        $message->respond();

        StatisticsLogger::webSocketMessage($connection->app->id);
    }

    /**
     * Handle the websocket close.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function onClose(ConnectionInterface $connection)
    {
        $this->channelManager->removeFromAllChannels($connection);

        DashboardLogger::log($connection->app->id, DashboardLogger::TYPE_DISCONNECTED, [
            'socketId' => $connection->socketId,
        ]);

        StatisticsLogger::disconnection($connection->app->id);

        $this->replicator->unsubscribeFromApp($connection->app->id);
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
        if ($exception instanceof WebSocketException) {
            $connection->send(json_encode(
                $exception->getPayload()
            ));
        }

        if (property_exists($connection, 'app')) {
            $this->replicator->unsubscribeFromApp($connection->app->id);
        }
    }

    /**
     * Verify the app key validity.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return $this
     */
    protected function verifyAppKey(ConnectionInterface $connection)
    {
        $appKey = QueryParameters::create($connection->httpRequest)->get('appKey');

        if (! $app = App::findByKey($appKey)) {
            throw new UnknownAppKey($appKey);
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
            throw new OriginNotAllowed($connection->app->key);
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
            $connectionsCount = $this->channelManager->getGlobalConnectionsCount($connection->app->id);

            if ($connectionsCount instanceof PromiseInterface) {
                $connectionsCount->then(function ($connectionsCount) use ($capacity, $connection) {
                    $connectionsCount = $connectionsCount ?: 0;

                    $this->sendExceptionIfOverCapacity($connectionsCount, $capacity, $connection);
                });
            } else {
                $this->throwExceptionIfOverCapacity($connectionsCount, $capacity);
            }
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

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $connection->httpRequest;

        DashboardLogger::log($connection->app->id, DashboardLogger::TYPE_CONNECTED, [
            'origin' => "{$request->getUri()->getScheme()}://{$request->getUri()->getHost()}",
            'socketId' => $connection->socketId,
        ]);

        StatisticsLogger::connection($connection->app->id);

        $this->replicator->subscribeToApp($connection->app->id);

        return $this;
    }

    /**
     * Throw a ConnectionsOverCapacity exception.
     *
     * @param  int  $connectionsCount
     * @param  int  $capacity
     * @return void
     * @throws ConnectionsOverCapacity
     */
    protected function throwExceptionIfOverCapacity(int $connectionsCount, int $capacity)
    {
        if ($connectionsCount >= $capacity) {
            throw new ConnectionsOverCapacity;
        }
    }

    /**
     * Send the ConnectionsOverCapacity exception through
     * the connection and close the channel.
     *
     * @param  int  $connectionsCount
     * @param  int  $capacity
     * @param  ConnectionInterface  $connection
     * @return void
     */
    protected function sendExceptionIfOverCapacity(int $connectionsCount, int $capacity, ConnectionInterface $connection)
    {
        if ($connectionsCount >= $capacity) {
            $payload = json_encode((new ConnectionsOverCapacity)->getPayload());

            tap($connection)->send($payload)->close();
        }
    }
}
